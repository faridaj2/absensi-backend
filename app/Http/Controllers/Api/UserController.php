<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->whereIn('role', [User::ROLE_GURU, User::ROLE_PEGAWAI])
            ->with('instansi');

        if ($request->user()->isAdmin()) {
            $query->where('instansi_id', $request->user()->instansi_id);
        }

        return UserResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['role'] = in_array($data['role'], [User::ROLE_GURU, User::ROLE_PEGAWAI], true)
            ? $data['role']
            : User::ROLE_GURU;

        if (isset($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $user = User::create($data);

        return new UserResource($user->load('instansi'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validated();
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

        return response()->json(['message' => 'User dihapus.']);
    }
}
