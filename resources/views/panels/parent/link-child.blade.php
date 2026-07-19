<x-app-layout>
    <x-panel-page title="ربط ابن" subtitle="اطلب كود الطالب من لوحة ابنك، ثم أرسل الطلب.">
        <x-slot:actions>
            <a href="{{ route('parent.dashboard') }}" class="link-brand">رجوع ↗</a>
        </x-slot:actions>

        <div class="max-w-lg">
            <livewire:parent.request-child-link />
        </div>
    </x-panel-page>
</x-app-layout>
