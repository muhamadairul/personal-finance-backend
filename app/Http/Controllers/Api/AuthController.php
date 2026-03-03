<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Helper to build user data array consistently.
     */
    private function userData(User $user, ?string $photoUrlOverride = null): array
    {
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'photo_url'          => $photoUrlOverride ?? ($user->photo_url ? asset('storage/' . $user->photo_url) : null),
            'is_pro'             => $user->isPro(),
            'subscription_until' => $user->subscription_until?->toISOString(),
        ];
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user'  => $this->userData($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user'  => $this->userData($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->userData($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return response()->json([
            'data'    => $this->userData($user),
            'message' => 'Profil berhasil diperbarui',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // Delete old photo if exists
        if ($user->photo_url && Storage::disk('public')->exists($user->photo_url)) {
            Storage::disk('public')->delete($user->photo_url);
        }

        // Store new photo in storage/app/public/profile-photos
        $path = $request->file('photo')->store('profile-photos', 'public');

        $user->update(['photo_url' => $path]);

        return response()->json([
            'data'    => $this->userData($user, asset('storage/' . $path)),
            'message' => 'Foto profil berhasil diperbarui',
        ]);
    }
}
