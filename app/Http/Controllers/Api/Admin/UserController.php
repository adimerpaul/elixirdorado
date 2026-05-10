<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('sucursal:id,nombre,slug')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'nickname' => $u->nickname,
                'email'    => $u->email,
                'rol'      => $u->rol,
                'sucursal' => $u->sucursal,
            ]);

        return response()->json([
            'users'      => $users,
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre', 'slug']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'nickname'    => 'nullable|string|max:50',
            'email'       => 'required|email|max:255|unique:users,email',
            'password'    => 'required|string',
            'rol'         => 'required|in:super_admin,admin,cajero',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $user = User::create([
            'name'        => $data['name'],
            'nickname'    => $data['nickname'] ?? null,
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'rol'         => $data['rol'],
            'sucursal_id' => $data['sucursal_id'] ?? null,
        ]);

        return response()->json($user->load('sucursal:id,nombre,slug'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('sucursal:id,nombre,slug'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'nickname'    => 'nullable|string|max:50',
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'    => 'nullable|string',
            'rol'         => 'required|in:super_admin,admin,cajero',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $user->update([
            'name'        => $data['name'],
            'nickname'    => $data['nickname'] ?? null,
            'email'       => $data['email'],
            'rol'         => $data['rol'],
            'sucursal_id' => $data['sucursal_id'] ?? null,
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);

        return response()->json($user->load('sucursal:id,nombre,slug'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'No puedes eliminar tu propio usuario.'], 422);
        }
        $user->delete();
        return response()->json(null, 204);
    }
}
