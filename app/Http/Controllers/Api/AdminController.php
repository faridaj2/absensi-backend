<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->whereIn('role', [User::ROLE_SUPERADMIN, User::ROLE_ADMIN])
            ->with('instansi')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        if ($data['role'] === User::ROLE_SUPERADMIN) {
            $data['instansi_id'] = null;
        }

        if (isset($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $admin = User::create($data);

        return new UserResource($admin->load('instansi'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if (isset($data['role']) && $data['role'] === User::ROLE_SUPERADMIN) {
            $data['instansi_id'] = null;
        }

        if (isset($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $user->update($data);

        return new UserResource($user->load('instansi'));
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'Admin dihapus.']);
    }
}
