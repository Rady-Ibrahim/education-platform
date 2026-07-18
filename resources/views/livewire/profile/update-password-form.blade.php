<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<form wire:submit="updatePassword" class="space-y-5">
    <div>
        <x-input-label for="update_password_current_password" value="كلمة المرور الحالية" />
        <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1.5 block w-full" autocomplete="current-password" />
        <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password" value="كلمة المرور الجديدة" />
        <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1.5 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="update_password_password_confirmation" value="تأكيد كلمة المرور" />
        <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
        <x-primary-button>تحديث كلمة المرور</x-primary-button>

        <x-action-message class="text-sm text-emerald-700" on="password-updated">
            تم التحديث
        </x-action-message>
    </div>
</form>
