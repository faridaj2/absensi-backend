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

        $admins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->with('instansi')
            ->orderBy('name')
            ->get();

        return UserResource::collection($admins);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['role'] = User::ROLE_ADMIN;
        $data['instansi_id'] = $data['instansi_id'] ?? null;

        $admin = User::create($data);

        return new UserResource($admin->load('instansi'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update($request->validated());

        return new UserResource($user->load('instansi'));
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'Admin dihapus.']);
    }
}
