<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    public function check()
    {
        $hasSuperadmin = User::where('role', User::ROLE_SUPERADMIN)->exists();
        return response()->json(['setup_required' => !$hasSuperadmin]);
    }

    public function setup(\Illuminate\Http\Request $request)
    {
        if (User::where('role', User::ROLE_SUPERADMIN)->exists()) {
            return response()->json(['message' => 'Setup sudah dilakukan.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_SUPERADMIN,
            'instansi_id' => null,
        ]);

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user,
            'message' => 'Superadmin berhasil dibuat.'
        ]);
    }
}
