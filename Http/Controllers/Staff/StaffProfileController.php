<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class StaffProfileController extends Controller
{
    public function show()
    {
        $staff = Auth::guard('staff')->user();
        return view('staff.profile.show', compact('staff'));
    }

    public function edit()
    {
        $staff = Auth::guard('staff')->user();
        return view('staff.profile.edit', compact('staff'));
    }

    public function update(Request $request)
    {
        $staff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Update profile picture if provided
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($staff->profile_picture) {
                Storage::delete($staff->profile_picture);
            }

            $path = $request->file('profile_picture')->store('staff/profile_pictures');
            $validated['profile_picture'] = $path;
        }

        $staff->update($validated);

        return redirect()->route('staff.profile.edit')
            ->with('success', 'Profile updated successfully');
    }

    public function showChangePassword()
    {
        return view('staff.profile.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $staff = Auth::guard('staff')->user();

        // Verify current password
        if (!Hash::check($request->current_password, $staff->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect']);
        }

        // Update password
        $staff->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        return redirect()->route('staff.profile.edit')
            ->with('success', 'Password changed successfully');
    }
}
