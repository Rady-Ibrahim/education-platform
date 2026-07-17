<?php

use App\Enums\UserRole;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\RegistrationService;
use App\Modules\Identity\Services\TeacherCatalogService;
use App\Modules\Identity\Services\TeacherJoinService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    #[Url]
    public string $role = 'student';

    #[Url]
    public string $join = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $headline = '';

    public string $bio = '';

    public string $vodafoneCashNumber = '';

    public string $paymentInstructions = '';

    public bool $isPubliclyVisible = false;

    public string $subjectMode = 'catalog';

    public ?int $subjectId = null;

    public ?int $gradeId = null;

    public string $subjectName = '';

    public function register(
        RegistrationService $registration,
        TeacherCatalogService $catalog,
        TeacherJoinService $joins,
    ): void {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', 'in:student,teacher,parent'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        if ($this->role === 'teacher') {
            $rules = array_merge($rules, [
                'headline' => ['nullable', 'string', 'max:160'],
                'bio' => ['nullable', 'string', 'max:2000'],
                'vodafoneCashNumber' => ['nullable', 'string', 'max:32'],
                'paymentInstructions' => ['nullable', 'string', 'max:2000'],
                'isPubliclyVisible' => ['boolean'],
                'subjectMode' => ['required', 'in:catalog,custom'],
            ]);

            if ($this->subjectMode === 'catalog') {
                $rules['subjectId'] = ['required', 'exists:subjects,id'];
            } else {
                $rules['gradeId'] = ['required', 'exists:grades,id'];
                $rules['subjectName'] = ['required', 'string', 'max:255'];
            }
        }

        $validated = $this->validate($rules);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => $validated['password'],
        ];

        if ($this->role === 'teacher') {
            $payload['headline'] = $this->headline !== '' ? $this->headline : null;
            $payload['bio'] = $this->bio !== '' ? $this->bio : null;
            $payload['vodafone_cash_number'] = $this->vodafoneCashNumber !== '' ? $this->vodafoneCashNumber : null;
            $payload['payment_instructions'] = $this->paymentInstructions !== '' ? $this->paymentInstructions : null;
            $payload['is_publicly_visible'] = $this->isPubliclyVisible;
            $payload['subject_mode'] = $this->subjectMode;
            $payload['subject_id'] = $this->subjectId;
            $payload['grade_id'] = $this->gradeId;
            $payload['subject_name'] = $this->subjectName !== '' ? $this->subjectName : null;
        }

        $user = $registration->register($payload);

        Auth::login($user);

        if ($user->hasRole(UserRole::Student) && $this->join !== '') {
            $teacher = $catalog->findPublicBySlug($this->join);
            if ($teacher) {
                $joins->requestJoin($user, $teacher, 'طلب انضمام من صفحة المدرس');
                $this->redirect(route('teachers.show', $teacher->slug, absolute: false), navigate: true);

                return;
            }
        }

        $this->redirect(route('dashboard', absolute: false), navigate: true);
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
            <select wire:model.live="role" id="role" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="student">طالب</option>
                <option value="teacher">مدرس</option>
                <option value="parent">ولي أمر</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
            <p class="mt-2 text-sm text-gray-500">
                الحساب يتفعّل فورًا. المدرس له مادة واحدة يحدّدها هنا أو يعدّلها لاحقًا من البروفايل.
                @if ($join !== '')
                    بعد التسجيل هنرسل طلب انضمام للمدرس الذي اخترته.
                @endif
            </p>
        </div>

        @if ($role === 'teacher')
            <div class="mt-4 space-y-4 rounded-md border border-teal-100 bg-teal-50/50 p-4">
                <p class="text-sm font-medium text-teal-900">بيانات المدرس</p>
                <div>
                    <x-input-label for="headline" value="تخصص / عنوان قصير" />
                    <x-text-input wire:model="headline" id="headline" class="block mt-1 w-full" placeholder="مدرس فيزياء — أولى ثانوي" />
                </div>
                <div>
                    <x-input-label for="bio" value="نبذة" />
                    <textarea wire:model="bio" id="bio" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>
                <div>
                    <x-input-label for="vodafoneCashNumber" value="رقم فودافون كاش" />
                    <x-text-input wire:model="vodafoneCashNumber" id="vodafoneCashNumber" class="block mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="paymentInstructions" value="تعليمات الدفع" />
                    <textarea wire:model="paymentInstructions" id="paymentInstructions" rows="2" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="الدفع نهاية الشهر كاش أو فودافون باسم المدرس"></textarea>
                </div>

                <div class="space-y-3 rounded-md border border-white bg-white/80 p-3">
                    <p class="text-sm font-medium text-teal-950">مادتك (واحدة فقط)</p>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="subjectMode" value="catalog" class="text-teal-700">
                            من كتالوج السنتر
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" wire:model.live="subjectMode" value="custom" class="text-teal-700">
                            أكتب اسم مادتي
                        </label>
                    </div>

                    @if ($subjectMode === 'catalog')
                        <div>
                            <x-input-label value="اختر المادة" />
                            <select wire:model="subjectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">—</option>
                                @foreach (Subject::query()->where('is_active', true)->where('is_custom', false)->with('grade.stage')->orderBy('name')->get() as $subject)
                                    <option value="{{ $subject->id }}">
                                        {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('subjectId')" class="mt-1" />
                        </div>
                    @else
                        <div>
                            <x-input-label value="الصف" />
                            <select wire:model="gradeId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">—</option>
                                @foreach (Grade::query()->where('is_active', true)->with('stage')->orderBy('ordering')->get() as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('gradeId')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="اسم المادة" />
                            <x-text-input wire:model="subjectName" class="mt-1 block w-full" placeholder="رياضيات" />
                            <x-input-error :messages="$errors->get('subjectName')" class="mt-1" />
                        </div>
                    @endif
                </div>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="isPubliclyVisible" class="mt-1 rounded border-gray-300 text-indigo-600">
                    <span>أظهر صفحتي في كتالوج المدرسين الآن</span>
                </label>
                <x-input-error :messages="$errors->get('isPubliclyVisible')" class="mt-1" />
            </div>
        @endif

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
