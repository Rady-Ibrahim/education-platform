<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التحقق من الشهادة</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen">
    <div class="max-w-xl mx-auto py-16 px-4">
        <div class="bg-white shadow-sm rounded-lg p-8 space-y-4">
            <h1 class="text-2xl font-semibold">التحقق من الشهادة</h1>
            <p class="text-sm text-gray-500">الرمز: <span class="font-mono">{{ $code }}</span></p>

            @if ($certificate)
                <div class="rounded-md bg-green-50 border border-green-200 p-4 space-y-2">
                    <div class="font-medium text-green-800">شهادة صحيحة ✓</div>
                    <div>{{ $certificate->title }}</div>
                    <div class="text-sm text-gray-700">الطالب: {{ $certificate->student->name }}</div>
                    @if ($certificate->subject)
                        <div class="text-sm text-gray-700">المادة: {{ $certificate->subject->name }}</div>
                    @endif
                    @if ($certificate->scorePercent() !== null)
                        <div class="text-sm text-gray-700">الدرجة: {{ $certificate->scorePercent() }}%</div>
                    @endif
                    <div class="text-sm text-gray-500">تاريخ الإصدار: {{ $certificate->issued_at->format('Y-m-d') }}</div>
                </div>
            @else
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-800">
                    لم يتم العثور على شهادة بهذا الرقم.
                </div>
            @endif
        </div>
    </div>
</body>
</html>
