<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
 public function profile()
    {
        $user = Auth::user();
        return apiResponse($user, 200, 'Profile retrieved successfully');
    }

    // Update user profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
        ]);

        return apiResponse($user, 200, 'Profile updated successfully');
    }

    // Update password
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return apiResponse(null, 400, 'ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវ');
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return apiResponse(null, 200, 'ពាក្យសម្ងាត់បានផ្លាស់ប្តូរដោយជោគជ័យ');
    }

    // Upload avatar
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::exists('public/avatars/' . $user->avatar)) {
            Storage::delete('public/avatars/' . $user->avatar);
        }

        // Upload new avatar
        $avatarName = time() . '.' . $request->avatar->extension();
        $request->avatar->storeAs('public/avatars', $avatarName);

        $user->avatar = $avatarName;
        $user->save();

        return apiResponse([
            'avatar' => asset('storage/avatars/' . $avatarName)
        ], 200, 'Avatar uploaded successfully');
    }

    // Get staff detail (for staff profile)
    public function getStaffDetail()
    {
        $user = Auth::user();
        $staffDetail = $user->staffDetail;
        
        return apiResponse([
            'user' => $user,
            'staff_detail' => $staffDetail
        ], 200, 'Staff detail retrieved successfully');
    }
}

