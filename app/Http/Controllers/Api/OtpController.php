<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpLogin;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    // POST /auth/otp/send
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Return success anyway to prevent email enumeration
            return response()->json(['message' => 'If that email is registered, a code has been sent.']);
        }

        // Invalidate any previous unused codes for this email
        OtpCode::where('email', $request->email)
            ->whereNull('used_at')
            ->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'email'      => $request->email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($request->email)->send(new OtpLogin($code, $user->name));

        return response()->json(['message' => 'If that email is registered, a code has been sent.']);
    }

    // POST /auth/otp/verify
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $otp = OtpCode::where('email', $request->email)
            ->where('code', $request->code)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp || !$otp->isValid()) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $otp->update(['used_at' => now()]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $token = $user->createToken('otp_login')->plainTextToken;

        return response()->json([
            'user'  => $user->load('roles'),
            'token' => $token,
        ]);
    }
}
