<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            حالة الحساب
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p class="text-lg font-medium">{{ $user->status->label() }}</p>

                    @if ($user->isPendingAdmin())
                        <p class="text-gray-600">
                            تم استلام طلبك بنجاح. الإدارة بتراجع بياناتك دلوقتي، وهتقدر تدخل المنصة بعد الموافقة.
                        </p>
                    @elseif ($user->status->value === 'rejected')
                        <p class="text-red-600">
                            تم رفض الحساب.
                            @if ($user->rejection_reason)
                                السبب: {{ $user->rejection_reason }}
                            @endif
                        </p>
                    @endif

                    <div class="text-sm text-gray-500">
                        البريد: {{ $user->email }}
                        @if ($user->primaryRole())
                            — الدور: {{ $user->primaryRole()->label() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
