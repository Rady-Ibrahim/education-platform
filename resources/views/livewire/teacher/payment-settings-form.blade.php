<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <x-input-label value="رقم فودافون كاش" />
            <x-text-input wire:model="vodafoneCashNumber" class="block mt-1 w-full" placeholder="01xxxxxxxxx" />
            <p class="text-xs text-gray-500 mt-1">سيظهر للطلاب وأولياء الأمور. الدفع غالبًا نهاية الشهر: كاش في السنتر أو فودافون كاش.</p>
        </div>
        <div>
            <x-input-label value="تعليمات التحويل" />
            <textarea wire:model="paymentInstructions" rows="4" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" placeholder="حوّل المبلغ نهاية الشهر باسم المدرس، ثم أرسل رقم العملية..."></textarea>
        </div>
        <x-primary-button type="submit">حفظ</x-primary-button>
    </form>
</div>
