<x-app-layout>
    <div class="surface-page">
        <div class="page-shell">
            <div class="max-w-2xl mx-auto">

                @php $status = $user->status; @endphp

                @if ($user->isPendingAdmin())
                    {{-- Teacher / Admin pending --}}
                    <div class="dashboard-block text-center py-12">
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 ring-1 ring-amber-100">
                            <svg class="h-8 w-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-ink">حسابك بانتظار موافقة الإدارة</h2>
                        <p class="mt-2 text-sm text-ink-muted max-w-md mx-auto">
                            تم استلام طلبك بنجاح. الإدارة بتراجع بياناتك دلوقتي، وهتقدر تدخل المنصة بعد الموافقة.
                        </p>
                        @if ($user->hasRole(\App\Enums\UserRole::Teacher))
                            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3 text-sm text-amber-900 max-w-sm mx-auto">
                                <p class="font-semibold">ملاحظة للمدرس</p>
                                <p class="mt-1 text-amber-800">بعد موافقة الإدارة، تحصل على فترة تجربة 90 يومًا مجانية.</p>
                            </div>
                        @endif
                        <div class="mt-6 flex justify-center">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-ghost">تسجيل الخروج</button>
                            </form>
                        </div>
                    </div>

                @elseif ($status === \App\Enums\UserStatus::PendingTeacher)
                    {{-- Student waiting for teacher approval --}}
                    <div class="dashboard-block text-center py-12">
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 ring-1 ring-brand-100">
                            <svg class="h-8 w-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-ink">في انتظار موافقة المدرس</h2>
                        <p class="mt-2 text-sm text-ink-muted max-w-md mx-auto">
                            تم إرسال طلب الانضمام للمدرس. بمجرد موافقته هتقدر تدخل المنصة وتبدأ.
                        </p>
                        <div class="mt-5 rounded-xl border border-brand-100 bg-brand-50/60 px-4 py-3 text-sm text-brand-900 max-w-sm mx-auto">
                            <p class="font-semibold">مستني إيه؟</p>
                            <p class="mt-1 text-brand-800">المدرس هيوافق على طلبك من لوحته — مش محتاج تعمل أي حاجة دلوقتي.</p>
                        </div>
                        <div class="mt-6 flex justify-center">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-ghost">تسجيل الخروج</button>
                            </form>
                        </div>
                    </div>

                @elseif ($status === \App\Enums\UserStatus::Rejected)
                    <div class="dashboard-block text-center py-12">
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 ring-1 ring-rose-100">
                            <svg class="h-8 w-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-ink">تم رفض الحساب</h2>
                        @if ($user->rejection_reason)
                            <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 max-w-md mx-auto">
                                {{ $user->rejection_reason }}
                            </p>
                        @endif
                        <div class="mt-6 flex justify-center">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-ghost">تسجيل الخروج</button>
                            </form>
                        </div>
                    </div>

                @else
                    <div class="dashboard-block text-center py-12">
                        <p class="text-sm text-ink-muted">{{ $user->status->label() }}</p>
                    </div>
                @endif

                <div class="mt-4 text-center text-xs text-ink-muted">
                    {{ $user->email }}
                    @if ($user->primaryRole())
                        — {{ $user->primaryRole()->label() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
