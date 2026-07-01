<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Show the forced change-password screen (accounts flagged must_change_password
     * are redirected here for everything else by the password.changed middleware).
     */
    public function mustChange(): View
    {
        return view('profile.must-change-password');
    }

    /**
     * Set a new password and clear the must_change_password flag. Requirements
     * (lower + upper + number, min 8) are kept simple on purpose — this screen
     * has to work for every skill level, not just technical staff.
     */
    public function mustChangeUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ], [
            'password.regex' => 'Password must include at least one lowercase letter, one uppercase letter, and one number.',
        ]);

        $request->user()->update([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password changed. Welcome!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (\App\Models\Setting::get('enable_user_delete', '1') !== '1') {
            abort(403, 'Deleting user accounts is currently disabled in System Settings.');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
