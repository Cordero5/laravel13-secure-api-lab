<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a tenant login account and create the matching tenant record,
     * so the mobile app and the admin dashboard share one identity.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'password'    => ['required', 'string', 'min:6'],
            'fcm_token'   => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'role'      => 'tenant',
                'fcm_token' => $data['fcm_token'] ?? null,
            ]);

            // The admin may have already recorded this person as a tenant and
            // assigned them a room. Claim that row instead of inserting a
            // duplicate, so the app shows the correct room and payment history.
            $tenant = Tenant::whereNull('user_id')
                ->where(fn ($q) => $q->where('email', $data['email']))
                ->first();

            if ($tenant) {
                $tenant->update([
                    'user_id' => $user->id,
                    'name'    => $tenant->name ?: $data['name'],
                    'phone'   => $tenant->phone ?: ($data['phone'] ?? null),
                ]);
            } else {
                Tenant::create([
                    'user_id' => $user->id,
                    'name'    => $data['name'],
                    'email'   => $data['email'],
                    'phone'   => $data['phone'] ?? null,
                ]);
            }

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registration successful',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user->load('tenant.room'),
        ], 201);
    }

    /**
     * Issue an API token, refreshing the stored device token when supplied.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'      => ['required', 'email'],
            'password'   => ['required', 'string'],
            'fcm_token'  => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!empty($data['fcm_token'])) {
            $user->forceFill(['fcm_token' => $data['fcm_token']])->save();
        }

        // Repair tenant accounts created before their matching profile existed.
        if ($user->role === 'tenant' && !$user->tenant) {
            $user->tenant()->create([
                'name'  => $user->name,
                'email' => $user->email,
            ]);
        }

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type'   => 'Bearer',
            'user'         => $user->load('tenant.room'),
        ]);
    }

    /**
     * Revoke the token used for the current request (logout).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
