<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
use \App\Http\Middleware\CheckAdmin;
use \App\Models\Room;
use \App\Models\Booking;
use \App\Models\Review;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/rooms/{room}/reviews', [ReviewController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('/bookings', BookingController::class);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::middleware([CheckAdmin::class])->group(function () {
        Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
        Route::get('/admin/stats', function () {
            return response()->json([
                'total_rooms' => Room::count(),
                'bookings_today' => Booking::whereDate('start_time', today())->count(),
                'new_reviews' => Review::where('is_approved', false)->count()
            ]);
        });
    });
});
