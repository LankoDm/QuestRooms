<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateRole(UserRequest $request, string $id){
        $request->validated();
        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();
        return response()->json([
           'message' => 'Роль успішно оновлено',
            'user' => $user
        ]);
    }
}
