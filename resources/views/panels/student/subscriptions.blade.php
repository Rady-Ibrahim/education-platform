<x-app-layout>
    <x-panel-page title="الاشتراكات والدفع" subtitle="اشترك في خطة مدرسك وأرسل إثبات الدفع عند الحاجة.">
        <x-slot:actions>
            <a href="{{ route('student.lessons') }}" class="link-brand" wire:navigate>الدروس</a>
            <a href="{{ route('student.exams') }}" class="link-brand" wire:navigate>الامتحانات</a>
        </x-slot:actions>

        <x-panel-card title="اشتراكاتك وإثبات الدفع" subtitle="اشترك في خطة مدرسك ثم أرسل إثبات فودافون كاش للمراجعة إن لزم.">
            <livewire:student.manage-subscriptions />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
