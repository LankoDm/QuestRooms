<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::withAvg(['reviews' => function ($q) {
            $q->where('is_approved', true);
        }], 'rating')->withCount(['reviews' => function ($q) {
            $q->where('is_approved', true);
        }]);
        if(!$request->has('show_all')){
            $query->where('is_active', 1);
        }
        if($request->has('difficulty')){
            $query->where('difficulty', $request->difficulty);
        }
        if($request->has('players_count')){
            $query->where('min_players', '<=', $request->players_count)->where('max_players', '>=', $request->players_count);
        }
        if($request->has('search')){
            $query->where('name', 'like', '%'.$request->search.'%');
        }
        if($request->has('sort')){
            switch ($request->sort) {
                case 'rating_desc':
                    $query->orderBy('reviews_avg_rating', 'desc');
                    break;
                case 'rating_asc':
                    $query->orderBy('reviews_avg_rating', 'asc');
                    break;
                case 'difficulty_asc':
                    $query->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard', 'ultra hard') ASC");
                    break;
                case 'difficulty_desc':
                    $query->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard', 'ultra hard') DESC");
                    break;
            }
        }else{
            $query->latest();
        }
        $rooms = $query->paginate(10);
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

    public function toggleStatus(Request $request, string $id)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);
        $room = Room::findOrFail($id);
        $room->is_active = $request->is_active;
        $room->save();
        return response()->json(['message' => 'Статус оновлено', 'is_active' => $room->is_active]);
    }

    public function destroy(string $id)
    {
        Room::findOrFail($id)->delete();
        return response()->json(["message" => "Кімнату успішно видалено."]);
    }
}
