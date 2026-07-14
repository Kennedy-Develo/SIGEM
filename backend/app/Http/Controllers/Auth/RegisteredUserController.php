<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    /**
     * Store a new access request.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = new User;

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Reader,
            'status' => UserStatus::Pending,
        ])->save();

        return response()->json([
            'message' => 'Solicitação de acesso enviada com sucesso. Aguarde a aprovação de um administrador.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ], 201);
    }
}
