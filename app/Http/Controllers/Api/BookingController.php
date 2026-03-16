<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\BookingCreated;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin() || $user->isManager()) {
            $bookings = Booking::with(['room'])->get();
        } else {
            $bookings = Booking::with(['room'])->where('user_id', $user->id)->get();
        }
        return response()->json($bookings);
    }

    public function store(BookingRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $room = Room::findOrFail($request->room_id);
            if ($request->players_count < $room->min_players || $request->players_count > $room->max_players) {
                return response()->json([
                    'message' => "Кількість гравців має бути від {$room->min_players} до {$room->max_players}"
                ], 422);
            }
            $startTime = Carbon::parse($request->start_time);
            $endTime = $startTime->copy()->addMinutes($room->duration_minutes);
            $isConflict = Booking::where('room_id', $room->id)
                ->where('status', '!=', 'cancelled')
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->lockForUpdate()
                ->exists();
            if ($isConflict) {
                return response()->json([
                    'message' => 'На жаль, цей час вже заброньовано іншими гравцями.'
                ], 422);
            }
            $basePrice = $startTime->isWeekend() ? $room->weekend_price : $room->weekday_price;
            $lateSurcharge = $startTime->hour >= 21 ? 20000 : 0;
            $extraPlayers = max(0, $request->players_count - $room->min_players);
            $playersSurcharge = $extraPlayers * 10000;
            $finalPrice = $basePrice + $lateSurcharge + $playersSurcharge;
            $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;
            $booking = Booking::create([
                'user_id' => $userId,
                'room_id' => $room->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'players_count' => $request->players_count,
                'total_price' => $finalPrice,
                'status' => 'pending',
                'guest_name' => $request->guest_name,
                'guest_phone' => $request->guest_phone,
                'guest_email' => $request->guest_email,
                'comment' => $request->comment,
                'payment_method' => $request->payment_method,
            ]);
            BookingCreated::dispatch($booking);
            return response()->json($booking, 201);
        });
    }

    public function show(Request $request, string $id)
    {
        $booking = Booking::with(['room'])->findOrFail($id);
        $user = $request->user();
        if ($user->id !== $booking->user_id) {
            return response()->json([
                'message' => 'Доступ заборонено. Ви не можете дивитися чужі бронювання.'
            ], 403);
        }
        return response()->json($booking);
    }

    public function update(BookingRequest $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([$request->validated()->only('status', 'admin_note')]);
        return response()->json($booking);
    }

    public function bookingConfirmation(string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'confirmed']);
        return response()->json(['message' => 'Бронювання підтвердженно.']);
    }

    public function bookingCancellation(string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Бронювання скасовано.']);
    }

    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();
        return response()->json(['message' => 'Бронювання видалено.']);
    }
}
