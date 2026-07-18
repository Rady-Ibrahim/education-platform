<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="space-y-5">
    <p class="text-sm leading-relaxed text-ink-muted">
        بمجرد حذف الحساب، تُحذف كل البيانات المرتبطة به نهائيًا. تأكد أنك حفظت أي معلومات تحتاجها قبل المتابعة.
    </p>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >حذف الحساب</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="text-lg font-bold text-ink">
                هل أنت متأكد من حذف الحساب؟
            </h2>

            <p class="mt-2 text-sm text-ink-muted">
                هذا الإجراء نهائي. أدخل كلمة المرور للتأكيد.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="كلمة المرور" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="كلمة المرور"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    إلغاء
                </x-secondary-button>

                <x-danger-button>
                    تأكيد الحذف
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</div>
