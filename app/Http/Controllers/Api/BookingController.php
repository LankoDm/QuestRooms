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
use App\Jobs\FinishBookingJob;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

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
            $cacheKey = "hold_room_{$room->id}_time_{$startTime->timestamp}";
            $holder = Cache::get($cacheKey);
            $identifier = $request->hold_token;
            if ($holder && $holder !== $identifier) {
                return response()->json([
                    'message' => 'На жаль, хтось інший вже почав оформлювати цей час.'
                ], 409);
            }
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
            Cache::forget($cacheKey);
            $booking->load('room');
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
        $booking->update($request->validated()->only('status', 'admin_note'));
        return response()->json($booking);
    }

    public function bookingConfirmation(string $id)
    {
        $booking = Booking::findOrFail($id);
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Бронювання вже оброблено'], 400);
        }
        $booking->status = 'confirmed';
        $booking->save();
        $finishTime = Carbon::parse($booking->end_time);
        FinishBookingJob::dispatch($booking->id)->delay($finishTime);
        return response()->json([
            'message' => 'Бронювання підтверджено. Задача на завершення створена.',
            'booking' => $booking
        ]);
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

    public function myBookings(Request $request)
    {
        $bookings = Booking::with('room:id,name,image_path,slug')
            ->where('user_id', $request->user()->id)
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json($bookings);
    }
    public function holdSlot(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'hold_token' => 'required|string',
        ]);
        $roomId = $request->room_id;
        $startTime = Carbon::parse($request->start_time);
        $isConflict = Booking::where('room_id', $roomId)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>', $startTime)
            ->exists();
        if ($isConflict) {
            return response()->json(['message' => 'Цей час вже заброньовано.'], 422);
        }
        $cacheKey = "hold_room_{$roomId}_time_{$startTime->timestamp}";
        $identifier = $request->hold_token;
        $locked = Cache::add($cacheKey, $identifier, now()->addMinutes(10));
        if (!$locked && Cache::get($cacheKey) !== $identifier) {
            return response()->json([
                'message' => 'Цей час зараз оформлює інший користувач. Спробуйте пізніше або виберіть інший час.'
            ], 409);
        }
        return response()->json(['message' => 'Час успішно зарезервовано на 10 хвилин.']);
    }
    public function downloadTicket(Booking $booking)
    {
        $pdf = Pdf::loadView('emails.booking_confirmed', [
            'booking' => $booking,
            'isPdf' => true
        ]);
        return $pdf->download("Ticket_Onea_Quests_{$booking->id}.pdf");
    }
}
