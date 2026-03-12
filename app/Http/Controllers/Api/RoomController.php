<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        return response()->json(Room::all());
    }

    public function store(RoomRequest $request)
    {
        $validateData = $request->validated();
        $room = Room::create($validateData);
        return response()->json($room, 201);
    }

    public function show(string $id)
    {
        return response()->json(Room::findOrFail($id));
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
        return response()->json(["message" => "Room deleted successfully."]);
    }
}
