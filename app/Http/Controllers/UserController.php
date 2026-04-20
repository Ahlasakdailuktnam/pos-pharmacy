<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    //
  public function register(Request $req){
        $data = $req->validate([
            'name'=> 'required|string|max:255',
            'email'=> 'required|email|unique:users,email',
            'date_of_birth'=> 'required|date',
            'phone'=> 'required|string',
            'id_card'=> 'required|min:6',
        ]);

        $data['password'] = Hash::make($data['id_card']);

        $user = User::create($data);

        return apiResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ],201,'register success');
    }

public function login(Request $req)
{
    $req->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (!Auth::attempt($req->only('email','password'))) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    $req->session()->regenerate();

    $user = auth()->user();

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin
        ]
    ]);
}
public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Logged out']);
}
}

