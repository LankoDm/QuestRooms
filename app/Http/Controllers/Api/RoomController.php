<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::withAvg(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }], 'rating')->withCount(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }])->get();
        return response()->json($rooms);
    }

    public function store(RoomRequest $request)
    {
        $validateData = $request->validated();
        if($request->hasFile('image_path')){
            $path = $request->file('image_path')->store('rooms', 'public');
            $validateData['image_path'] = url("storage/{$path}");
        }
        $room = Room::create($validateData);
        return response()->json($room, 201);
    }

    public function show(string $id)
    {
        $room = Room::withAvg(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }], 'rating')->withCount(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }])->findOrFail($id);
        return response()->json($room);
    }

    public function update(RoomRequest $request, string $id)
    {
        $validateData = $request->validated();
        $room = Room::findOrFail($id);
        $room->update($validateData);
        return response()->json($room, 200);
    }

    public function destroy(string $id)
    {
        Room::findOrFail($id)->delete();
        return response()->json(["message" => "Кімнату успішно видалено."]);
    }
}
