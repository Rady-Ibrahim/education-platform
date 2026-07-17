<div>
    @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($requests as $joinRequest)
            <div class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="font-medium">{{ $joinRequest->student->name }}</div>
                    <div class="text-sm text-gray-600">{{ $joinRequest->student->email }}</div>
                    @if ($joinRequest->message)
                        <div class="text-sm text-gray-500 mt-1">{{ $joinRequest->message }}</div>
                    @endif
                </div>
                <div class="flex gap-2">
                    <x-primary-button wire:click="approve({{ $joinRequest->id }})">قبول</x-primary-button>
                    <x-danger-button wire:click="reject({{ $joinRequest->id }})">رفض</x-danger-button>
                </div>
            </div>
        @empty
            <p class="text-gray-600">لا توجد طلبات انضمام حالياً.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
