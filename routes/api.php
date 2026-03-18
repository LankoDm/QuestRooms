<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckManager;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Review;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/rooms/{room}/reviews', [ReviewController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::post('/bookings/hold', [BookingController::class, 'holdSlot']);
Route::post('/bookings/{booking}/pay', [\App\Http\Controllers\Api\PaymentController::class, 'createCheckoutSession']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/bookings', [BookingController::class, 'myBookings']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::middleware([CheckManager::class])->group(function () {
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::patch('/bookings/{id}/confirm', [BookingController::class, 'bookingConfirmation']);
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'bookingCancellation']);

        Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
        Route::get('/reviews', [ReviewController::class, 'manageIndex']);
    });

    Route::middleware([CheckAdmin::class])->group(function () {
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);

        Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

        Route::get('/admin/stats', function () {
            return response()->json([
                'total_rooms' => Room::count(),
                'bookings_today' => Booking::whereDate('start_time', today())->count(),
                'new_reviews' => Review::where('is_approved', false)->count()
            ]);
        });
    });
});
