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
    #[Url] public string $role = 'student';
    #[Url] public string $join = '';
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
    public ?int $teacherId = null;
    public string $subjectName = '';

    public function mount(TeacherCatalogService $catalog): void
    {
        if ($this->role === 'student' && $this->join !== '') {
            $teacher = $catalog->findPublicBySlug($this->join);
            if ($teacher) {
                $this->teacherId = $teacher->id;
                $gradeId = $teacher->teachingSubjects()->value('grade_id');
                if ($gradeId) {
                    $this->gradeId = (int) $gradeId;
                }
            }
        }
    }

    public function updatedGradeId(): void
    {
        if ($this->role !== 'student' || ! $this->teacherId || ! $this->gradeId) {
            return;
        }
        $stillValid = app(TeacherCatalogService::class)
            ->listPublic($this->gradeId)
            ->contains('id', $this->teacherId);
        if (! $stillValid) {
            $this->teacherId = null;
        }
    }

    public function register(
        RegistrationService $registration,
        TeacherCatalogService $catalog,
        TeacherJoinService $joins,
    ): void {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role'     => ['required', 'in:student,teacher,parent'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];

        if ($this->role === 'student') {
            $rules['gradeId']   = ['required', 'exists:grades,id'];
            $rules['teacherId'] = ['required', 'integer', 'exists:users,id'];
        }

        if ($this->role === 'teacher') {
            $rules = array_merge($rules, [
                'headline'            => ['nullable', 'string', 'max:160'],
                'bio'                 => ['nullable', 'string', 'max:2000'],
                'vodafoneCashNumber'  => ['nullable', 'string', 'max:32'],
                'paymentInstructions' => ['nullable', 'string', 'max:2000'],
                'isPubliclyVisible'   => ['boolean'],
                'subjectMode'         => ['required', 'in:catalog,custom'],
            ]);
            if ($this->subjectMode === 'catalog') {
                $rules['subjectId'] = ['required', 'exists:subjects,id'];
            } else {
                $rules['gradeId']     = ['required', 'exists:grades,id'];
                $rules['subjectName'] = ['required', 'string', 'max:255'];
            }
        }

        $validated = $this->validate($rules);

        $selectedTeacher = null;
        if ($this->role === 'student') {
            $selectedTeacher = $catalog->findPublicById((int) $this->teacherId);
            if (! $selectedTeacher) {
                $this->addError('teacherId', 'اختر مدرسًا ظاهرًا من القائمة.');
                return;
            }
            if ($this->gradeId) {
                $teachesGrade = $selectedTeacher->teachingSubjects()
                    ->where('grade_id', $this->gradeId)
                    ->exists();
                if (! $teachesGrade) {
                    $this->addError('teacherId', 'المدرس المختار لا يدرّس صفك.');
                    return;
                }
            }
        }

        $payload = [
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'role'     => $validated['role'],
            'password' => $validated['password'],
        ];

        if ($this->role === 'student') {
            $payload['grade_id'] = $this->gradeId;
        }

        if ($this->role === 'teacher') {
            $payload['headline']             = $this->headline !== '' ? $this->headline : null;
            $payload['bio']                  = $this->bio !== '' ? $this->bio : null;
            $payload['vodafone_cash_number'] = $this->vodafoneCashNumber !== '' ? $this->vodafoneCashNumber : null;
            $payload['payment_instructions'] = $this->paymentInstructions !== '' ? $this->paymentInstructions : null;
            $payload['is_publicly_visible']  = $this->isPubliclyVisible;
            $payload['subject_mode']         = $this->subjectMode;
            $payload['subject_id']           = $this->subjectId;
            $payload['grade_id']             = $this->gradeId;
            $payload['subject_name']         = $this->subjectName !== '' ? $this->subjectName : null;
        }

        $user = $registration->register($payload);
        Auth::login($user);

        if ($user->hasRole(UserRole::Student) && $selectedTeacher) {
            $joins->requestJoin($user, $selectedTeacher, 'طلب انضمام عند التسجيل');
            $this->redirect(route('teachers.show', $selectedTeacher->slug, absolute: false), navigate: true);
            return;
        }

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-ink">إنشاء حساب جديد</h1>
        <p class="mt-1.5 text-sm text-ink-muted">
            @if ($role === 'teacher')
                سجّل كمدرس وأعدّ مكتبك من اليوم الأول.
            @else
                انضم لمدرسك وابدأ رحلتك التعليمية.
            @endif
        </p>
    </div>

    {{-- Role switcher (teacher / student only — parent accounts are created by teachers) --}}
    <div class="mb-6 flex rounded-xl border border-slate-200/80 bg-slate-100/80 p-1">
        @foreach (['student' => 'طالب', 'teacher' => 'مدرس'] as $r => $label)
            <button
                type="button"
                wire:click="$set('role', '{{ $r }}')"
                @class([
                    'flex-1 rounded-lg py-2 text-sm font-semibold transition duration-200',
                    'bg-white text-brand-950 shadow-soft ring-1 ring-slate-200/80' => $role === $r,
                    'text-ink-muted hover:text-ink' => $role !== $r,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <form wire:submit="register" class="space-y-4">

        {{-- Basic info --}}
        <div class="space-y-4">
            <div>
                <x-input-label for="name" value="الاسم الكامل" />
                <x-text-input wire:model="name" id="name" class="mt-1.5 block w-full" type="text" required autofocus autocomplete="name" placeholder="محمد أحمد" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="email" value="البريد الإلكتروني" />
                <x-text-input wire:model="email" id="email" class="mt-1.5 block w-full" type="email" required autocomplete="username" placeholder="name@example.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="phone" value="رقم الهاتف (اختياري)" />
                <x-text-input wire:model="phone" id="phone" class="mt-1.5 block w-full" type="text" autocomplete="tel" placeholder="01x xxxx xxxx" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
            </div>
        </div>

        {{-- Student section --}}
        @if ($role === 'student')
            @php
                $availableTeachers = $gradeId
                    ? app(TeacherCatalogService::class)->listPublic($gradeId)
                    : collect();
            @endphp

            <div class="space-y-4 rounded-2xl border border-brand-100 bg-brand-50/40 p-4">
                <p class="text-xs font-bold uppercase tracking-[0.1em] text-brand-700">بيانات الطالب</p>
                <div>
                    <x-input-label for="gradeId" value="الصف الدراسي" />
                    <select wire:model.live="gradeId" id="gradeId" class="mt-1.5 block w-full">
                        <option value="">اختر صفك — مثلاً أولى ثانوي</option>
                        @foreach (Grade::query()->where('is_active', true)->with('stage')->orderBy('ordering')->get() as $grade)
                            <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('gradeId')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="teacherId" value="اختر مدرسك" />
                    <select wire:model="teacherId" id="teacherId" class="mt-1.5 block w-full">
                        <option value="">
                            @if ($gradeId && $availableTeachers->isEmpty())
                                لا يوجد مدرسون ظاهرون لهذا الصف
                            @elseif ($gradeId)
                                اختر مدرسك
                            @else
                                اختَر الصف أولاً
                            @endif
                        </option>
                        @foreach ($availableTeachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->name }}@if ($teacher->headline) — {{ $teacher->headline }}@endif
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('teacherId')" class="mt-1.5" />
                    <p class="mt-1.5 text-xs text-ink-muted">بعد التسجيل هيتبعت طلب انضمام للمدرس عشان يوافق عليه.</p>
                </div>
            </div>
        @endif

        {{-- Teacher section --}}
        @if ($role === 'teacher')
            <div class="space-y-4 rounded-2xl border border-brand-100 bg-brand-50/40 p-4">
                <p class="text-xs font-bold uppercase tracking-[0.1em] text-brand-700">بيانات المدرس</p>

                <div>
                    <x-input-label for="headline" value="تخصص / عنوان قصير" />
                    <x-text-input wire:model="headline" id="headline" class="mt-1.5 block w-full" placeholder="مدرس فيزياء — أولى ثانوي" />
                    <x-input-error :messages="$errors->get('headline')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="bio" value="نبذة عنك (اختياري)" />
                    <textarea wire:model="bio" id="bio" rows="3" class="mt-1.5 block w-full" placeholder="اكتب نبذة مختصرة تظهر في صفحتك العامة..."></textarea>
                    <x-input-error :messages="$errors->get('bio')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="vodafoneCashNumber" value="رقم فودافون كاش (اختياري)" />
                    <x-text-input wire:model="vodafoneCashNumber" id="vodafoneCashNumber" class="mt-1.5 block w-full" placeholder="01x xxxx xxxx" />
                    <x-input-error :messages="$errors->get('vodafoneCashNumber')" class="mt-1.5" />
                </div>

                {{-- Subject selection --}}
                <div class="space-y-3 rounded-xl border border-white bg-white/80 p-4">
                    <p class="text-sm font-bold text-ink">مادتك الدراسية</p>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="subjectMode" value="catalog" class="text-brand-700 focus:ring-brand-500">
                            <span>من قائمة المواد</span>
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="subjectMode" value="custom" class="text-brand-700 focus:ring-brand-500">
                            <span>أكتب اسم مادتي</span>
                        </label>
                    </div>

                    @if ($subjectMode === 'catalog')
                        <div>
                            <x-input-label value="اختر المادة" />
                            <select wire:model="subjectId" class="mt-1.5 block w-full">
                                <option value="">—</option>
                                @foreach (Subject::query()->where('is_active', true)->where('is_custom', false)->with('grade.stage')->orderBy('name')->get() as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('subjectId')" class="mt-1.5" />
                        </div>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-input-label value="الصف" />
                                <select wire:model="gradeId" class="mt-1.5 block w-full">
                                    <option value="">—</option>
                                    @foreach (Grade::query()->where('is_active', true)->with('stage')->orderBy('ordering')->get() as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->stage?->name }} — {{ $grade->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('gradeId')" class="mt-1.5" />
                            </div>
                            <div>
                                <x-input-label value="اسم المادة" />
                                <x-text-input wire:model="subjectName" class="mt-1.5 block w-full" placeholder="رياضيات" />
                                <x-input-error :messages="$errors->get('subjectName')" class="mt-1.5" />
                            </div>
                        </div>
                    @endif
                </div>

                <label class="flex cursor-pointer items-start gap-2.5 text-sm">
                    <input type="checkbox" wire:model="isPubliclyVisible" class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    <span class="text-ink-muted">أظهر صفحتي في كتالوج المدرسين الآن</span>
                </label>
            </div>
        @endif

        {{-- Password --}}
        <div class="space-y-4">
            <div>
                <x-input-label for="password" value="كلمة المرور" />
                <x-text-input wire:model="password" id="password" class="mt-1.5 block w-full" type="password" required autocomplete="new-password" placeholder="8 أحرف على الأقل" />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="تأكيد كلمة المرور" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1.5 block w-full" type="password" required autocomplete="new-password" placeholder="••••••••" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>
        </div>

        {{-- Submit --}}
        <x-primary-button class="mt-2 w-full justify-center py-3 text-base">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            إنشاء الحساب
        </x-primary-button>

        <div class="border-t border-slate-100 pt-4 text-center">
            <span class="text-sm text-ink-muted">لديك حساب؟</span>
            <a href="{{ route('login') }}" wire:navigate class="ms-1 text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                تسجيل الدخول
            </a>
        </div>
    </form>
</div>
