<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Mail\ResetPasswordOtp;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * Send OTP to the user's email.
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email'], [
            'email.exists' => 'Kami tidak dapat menemukan pengguna dengan alamat email tersebut.'
        ]);

        $email = $request->email;
        $otp = (string) random_int(100000, 999999);

        // Delete existing token if any
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Insert new token
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($otp),
            'created_at' => Carbon::now()
        ]);

        // Send Email
        Mail::to($email)->send(new ResetPasswordOtp($otp));

        return response()->json(['message' => 'Kode OTP telah dikirim ke email Anda.']);
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6'
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            throw ValidationException::withMessages(['otp' => ['Kode OTP tidak valid atau salah.']]);
        }

        // Check expiration (e.g. 15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages(['otp' => ['Kode OTP sudah kadaluarsa.']]);
        }

        return response()->json(['message' => 'Kode OTP valid.']);
    }

    /**
     * Reset the password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            throw ValidationException::withMessages(['otp' => ['Kode OTP tidak valid atau salah.']]);
        }

        // Check expiration
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages(['otp' => ['Kode OTP sudah kadaluarsa.']]);
        }

        // Reset password
        $user = User::where('email', $request->email)->first();
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        // Delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password berhasil diubah. Silakan login dengan password baru.']);
    }
}
