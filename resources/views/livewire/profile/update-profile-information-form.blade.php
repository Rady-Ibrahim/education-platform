<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<form wire:submit="updateProfileInformation" class="space-y-5">
    <div>
        <x-input-label for="name" value="الاسم" />
        <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1.5 block w-full" required autofocus autocomplete="name" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="email" value="البريد الإلكتروني" />
        <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1.5 block w-full" required autocomplete="username" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                <p>
                    بريدك غير مفعّل.
                    <button wire:click.prevent="sendVerification" class="font-semibold underline underline-offset-2 hover:text-amber-950">
                        إعادة إرسال رابط التفعيل
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 font-medium text-green-700">
                        تم إرسال رابط تفعيل جديد إلى بريدك.
                    </p>
                @endif
            </div>
        @endif
    </div>

    <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
        <x-primary-button>حفظ التغييرات</x-primary-button>

        <x-action-message class="text-sm text-emerald-700" on="profile-updated">
            تم الحفظ
        </x-action-message>
    </div>
</form>
