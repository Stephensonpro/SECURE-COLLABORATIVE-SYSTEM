<?php

namespace App\Http\Controllers\Staff\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff;
use App\Models\StaffVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\StaffVerificationCode;
use Illuminate\Support\Facades\Session;

class StaffAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.staff.login');
    }



    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('staff')->attempt($credentials)) {
            $request->session()->regenerate();
            $staff = Auth::guard('staff')->user();

            // Update last login time
            $staff->update(['last_login_at' => now()]);

            // Check if password needs to be changed (first login)
            if ($staff->password_changed_at === null) {
                return redirect()->route('staff.password.change');
            }

            // Check if email is verified
            if (!$staff->email_verified_at) {
                $this->sendVerificationCode($staff);
                return redirect()->route('staff.verify');
            }

            return redirect()->intended(route('staff.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }









    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/staff/login');
    }

    // Password Change Flow
    public function showChangePasswordForm()
    {
        $staff = Auth::guard('staff')->user();

        // Only allow access if password hasn't been changed yet
        if ($staff->password_changed_at !== null) {
            return redirect()->route('staff.dashboard');
        }

        return view('staff.auth.change-password');
    }






    // Verification Flow
    public function showVerifyForm()
    {
        $staff = Auth::guard('staff')->user();

        // Only allow access if not verified
        if ($staff->email_verified_at) {
            return redirect()->route('staff.dashboard');
        }

        return view('staff.verify');
    }


    public function verifyEmail(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|array|size:6',
        ]);

        $enteredCode = implode('', $request->verification_code);
        $staff = Auth::guard('staff')->user();

        $verification = StaffVerification::where('staff_id', $staff->id)
            ->where('code', $enteredCode)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return back()->withErrors(['verification_code' => 'Invalid or expired verification code']);
        }

        // Update both email verification and password change status
        $staff->email_verified_at = now();

        // Ensure password_changed_at is set if not already
        if ($staff->password_changed_at === null) {
            $staff->password_changed_at = now();
        }

        $staff->save();
        $verification->delete();

        return redirect()->route('staff.dashboard')
            ->with('verified', 'Email verified successfully!');
    }

    public function changePassword(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $rules = [
            'new_password' => 'required|min:8|confirmed',
        ];

        if ($staff->password_changed_at !== null) {
            $rules['current_password'] = 'required';
        }

        $request->validate($rules);

        if ($staff->password_changed_at !== null &&
            !Hash::check($request->current_password, $staff->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $staff->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now()
        ]);

        // If email wasn't verified yet, send verification code
        if (!$staff->email_verified_at) {
            $this->sendVerificationCode($staff);

            if ($request->ajax()) {
                return response()->json([
                    'redirect' => route('staff.verify'),
                    'message' => 'Password changed successfully! Please verify your email.'
                ]);
            }

            return redirect()->route('staff.verify')
                ->with('success', 'Password changed successfully! Please verify your email.');
        }

        if ($request->ajax()) {
            return response()->json([
                'redirect' => route('staff.dashboard'),
                'message' => 'Password changed successfully!'
            ]);
        }

        return redirect()->route('staff.dashboard')
            ->with('success', 'Password changed successfully!');
    }


    public function resendVerification(Request $request)
    {
        $staff = Auth::guard('staff')->user();
        $this->sendVerificationCode($staff);

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.'
        ]);
    }

    protected function sendVerificationCode($staff)
    {
        // Delete any existing verification codes
        StaffVerification::where('staff_id', $staff->id)->delete();

        // Generate 6-digit numeric code
        $code = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        StaffVerification::create([
            'staff_id' => $staff->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(30)
        ]);

        Mail::to($staff->email)->send(new StaffVerificationCode($code));
    }





//Forgot Password

// Add these methods to your StaffAuthController

public function showForgotPasswordForm()
{
    return view('auth.staff.forgot-password');
}

public function sendResetLinkEmail(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $staff = Staff::where('email', $request->email)->first();

    if (!$staff) {
        return back()->withErrors(['email' => 'We could not find a staff member with that email address.']);
    }

    // Generate verification code and send email
    $this->sendVerificationCode($staff);

    // Store staff email in session for verification
    Session::put('staff_password_reset_email', $staff->email);

    return redirect()->route('staff.password.verify')
        ->with('status', 'We have emailed your password reset verification code!');
}

public function showVerifyPasswordForm()
{
    if (!Session::has('staff_password_reset_email')) {
        return redirect()->route('staff.password.request');
    }

    return view('auth.staff.verify-password');
}

public function verifyPasswordReset(Request $request)
{
    $request->validate(['verification_code' => 'required|array|size:6']);

    if (!Session::has('staff_password_reset_email')) {
        return redirect()->route('staff.password.request');
    }

    $staff = Staff::where('email', Session::get('staff_password_reset_email'))->first();
    $enteredCode = implode('', $request->verification_code);

    $verification = StaffVerification::where('staff_id', $staff->id)
        ->where('code', $enteredCode)
        ->where('expires_at', '>', now())
        ->first();

    if (!$verification) {
        return back()->withErrors(['verification_code' => 'Invalid or expired verification code']);
    }

    // Store verification in session for password reset
    Session::put('staff_password_reset_verified', true);
    $verification->delete();

    return redirect()->route('staff.password.reset');
}

public function showResetPasswordForm(Request $request)
{
    if (!Session::has('staff_password_reset_verified')) {
        return redirect()->route('staff.password.request');
    }

    return view('auth.staff.reset-password', [
        'email' => Session::get('staff_password_reset_email')
    ]);
}

public function resetPassword(Request $request)
{
    if (!Session::has('staff_password_reset_verified')) {
        return redirect()->route('staff.password.request');
    }

    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $staff = Staff::where('email', $request->email)->first();

    if (!$staff) {
        return back()->withErrors(['email' => 'Invalid email address']);
    }

    $staff->update([
        'password' => Hash::make($request->password),
        'password_changed_at' => now()
    ]);

    // Clear the session
    Session::forget(['staff_password_reset_email', 'staff_password_reset_verified']);

    return redirect()->route('staff.login')
        ->with('status', 'Your password has been reset!');
}
}
