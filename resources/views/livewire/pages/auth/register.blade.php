<?php

use App\Enums\UserRole;
use App\Modules\Identity\Services\RegistrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'student';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(RegistrationService $registration): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', 'in:student,teacher'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $registration->register($validated);

        Auth::login($user);

        $this->redirect(route('account.pending', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <div>
            <x-input-label for="name" value="الاسم" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="البريد الإلكتروني" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" value="رقم الهاتف (اختياري)" />
            <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="role" value="نوع الحساب" />
            <select wire:model="role" id="role" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="student">طالب</option>
                <option value="teacher">مدرس</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
            <p class="mt-2 text-sm text-gray-500">بعد التسجيل ينتظر حسابك موافقة الإدارة قبل الدخول للمنصة.</p>
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="كلمة المرور" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="تأكيد كلمة المرور" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                لديك حساب؟
            </a>

            <x-primary-button class="ms-4">
                إنشاء حساب
            </x-primary-button>
        </div>
    </form>
</div>
