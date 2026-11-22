<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BankAccountUpdateRequest;
use App\Http\Requests\Settings\ProfileAvatarUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        unset($validated['avatar'], $validated['remove_avatar']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $this->replaceLocalAvatar($user, $request->file('avatar'));
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteLocalAvatar($user->avatar);
            $user->avatar = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return back();
    }

    /**
     * Upload and immediately update the authenticated user's profile photo.
     */
    public function updateAvatar(ProfileAvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->replaceLocalAvatar($user, $request->file('avatar'));
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto profil berhasil diperbarui.']);

        return back();
    }

    /**
     * Remove the authenticated user's profile photo.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->deleteLocalAvatar($user->avatar);
        $user->forceFill(['avatar' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Foto profil berhasil dihapus.']);

        return back();
    }

    /**
     * Show the user's payout bank account settings page.
     */
    public function editBank(): Response
    {
        return Inertia::render('settings/Bank');
    }

    /**
     * Update the user's payout bank account information.
     */
    public function updateBank(BankAccountUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rekening pencairan berhasil disimpan.']);

        return back();
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $this->deleteLocalAvatar($user->avatar);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function replaceLocalAvatar(User $user, UploadedFile $file): void
    {
        $this->deleteLocalAvatar($user->avatar);
        $storedPath = $file->store('avatars', 'public');
        $user->avatar = '/storage/'.$storedPath;
    }

    private function deleteLocalAvatar(?string $avatarUrl): void
    {
        if (! is_string($avatarUrl) || ! str_starts_with($avatarUrl, '/storage/')) {
            return;
        }

        $relativePath = substr($avatarUrl, strlen('/storage/'));
        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
