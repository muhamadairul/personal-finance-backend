<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            'phone'              => $user->phone,
            'address'            => $user->address,
            'date_of_birth'      => $user->date_of_birth?->toDateString(),
            'gender'             => $user->gender,
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

    /**
     * POST /api/auth/social
     * Handle social login (Google Sign-In).
     *
     * Flow:
     * 1. Receive provider, id_token, name, email from mobile app
     * 2. Verify the ID token with Google
     * 3. Find or create user by provider+provider_id or email
     * 4. Issue Sanctum token
     */
    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google',
            'id_token' => 'required|string',
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255',
        ]);

        $provider = $request->provider;
        $idToken  = $request->id_token;

        // Verify the ID token
        $providerUserId = $this->verifyIdToken($provider, $idToken);

        if (!$providerUserId) {
            return response()->json([
                'message' => 'Token autentikasi tidak valid.',
            ], 401);
        }

        // Find existing user by provider + provider_id
        $user = User::where('provider', $provider)
            ->where('provider_id', $providerUserId)
            ->first();

        if (!$user) {
            // Check if a user with the same email already exists (registered via email/password)
            $user = User::where('email', $request->email)->first();

            if ($user) {
                // Link existing email-registered account to social provider
                $user->update([
                    'provider'    => $provider,
                    'provider_id' => $providerUserId,
                ]);
            } else {
                // Create a brand-new user (no password needed for social login)
                $user = User::create([
                    'name'        => $request->name,
                    'email'       => $request->email,
                    'provider'    => $provider,
                    'provider_id' => $providerUserId,
                    'password'    => null,
                ]);
            }
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user'  => $this->userData($user),
            'token' => $token,
        ]);
    }

    /**
     * Verify an OAuth ID token with the provider.
     * Returns the provider user ID on success, or null on failure.
     */
    private function verifyIdToken(string $provider, string $idToken): ?string
    {
        if ($provider === 'google') {
            return $this->verifyGoogleIdToken($idToken);
        }

        return null;
    }

    /**
     * Verify Google ID Token via Google's tokeninfo endpoint.
     */
    private function verifyGoogleIdToken(string $idToken): ?string
    {
        try {
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

            if (!$response->successful()) {
                Log::warning('Google token verification failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            // Validate audience matches our client IDs
            $validClientIds = array_filter([
                config('services.google.client_id'),
                config('services.google.client_id_android'),
            ]);

            if (!in_array($data['aud'] ?? '', $validClientIds)) {
                Log::warning('Google token audience mismatch', [
                    'aud'      => $data['aud'] ?? 'N/A',
                    'expected' => $validClientIds,
                ]);
                return null;
            }

            // Return the Google user ID (sub claim)
            return $data['sub'] ?? null;
        } catch (\Exception $e) {
            Log::error('Google token verification error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * POST /api/user/fcm-token
     * Store or update the user's FCM device token for push notifications.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string|max:255',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'FCM token berhasil diperbarui',
        ]);
    }

    public function logout(Request $request)
    {
        // Clear FCM token on logout so we stop sending push to this device
        $request->user()->update(['fcm_token' => null]);

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
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:L,P',
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

    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->photo_url && Storage::disk('public')->exists($user->photo_url)) {
            Storage::disk('public')->delete($user->photo_url);
        }

        $user->update(['photo_url' => null]);

        return response()->json([
            'data'    => $this->userData($user),
            'message' => 'Foto profil berhasil dihapus',
        ]);
    }
}
