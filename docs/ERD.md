# ERD — منصة تعليمية (مسودة المرحلة 0)

> يُحدَّث مع كل Module. الحالة: أساس جاهز للتنفيذ.

## قرارات Schema مثبتة
- `branch_id` جاهز من اليوم الأول (فرع واحد افتراضي).
- الدفع يدوي: فودافون كاش / كاش — بدون webhook بوابة الآن.
- فيديو الدروس: Bunny Stream (`bunny_video_id`).
- إجابات الامتحان تُحفظ سطرًا بسطر (autosave).
- المدرس يضيف طلاب (`users.created_by`) ويسجّل مدفوعات ضمن نطاقه (`payments.teacher_id`).

---

## الكيانات الحالية (منفّذة)

### branches
| العمود | النوع | ملاحظات |
|---|---|---|
| id | bigint | PK |
| name | string | |
| code | string | unique |
| is_default | bool | فرع واحد افتراضي |
| is_active | bool | |
| timestamps / softDeletes | | |

### users
| العمود | النوع | ملاحظات |
|---|---|---|
| id | bigint | PK |
| name | string | |
| email | string | unique — **تسجيل الدخول بالإيميل** |
| phone | string nullable | unique — تواصل فقط |
| student_code | string nullable | unique |
| branch_id | FK nullable | → branches |
| created_by | FK nullable | → users |
| status | string | pending_admin / active / rejected / suspended |
| approved_at / approved_by | | |
| rejection_reason | text nullable | |
| password | string | |
| email_verified_at | timestamp nullable | |
| softDeletes | | |

### teacher_join_requests
| العمود | ملاحظات |
|---|---|
| student_id, teacher_id | unique معًا |
| status | pending / approved / rejected |
| message, review_note, reviewed_at | |

### teacher_student
pivot انضمام فعلي بعد موافقة المدرس أو إضافة يدوية من المكتب

### lessons / lesson_attachments / lesson_progress
- Lesson: unit_id, created_by, title, type (text|video|mixed), body, bunny_video_id, ordering, is_published
- Attachment: path, is_downloadable
- Progress: student_id, percent, watched_seconds, is_completed
- وصول الطالب: مرتبط بمدرس يدرّس المادة (+ اشتراك مدفوع لاحقًا)

### questions / exams / exam_attempts / exam_answers
- Question types: mcq, true_false, essay, fill_blank
- exam_answers تُحفظ فورًا (autosave) عبر saved_at
- Response للطالب أثناء الامتحان بدون correct_answer / is_correct

### stages / grades / subjects / units
- Stage 1──* Grade 1──* Subject 1──* Unit
- كل مستوى: name, code, ordering, is_active, softDeletes

### teacher_subject
ربط معلم ↔ مادة (عزل محتوى المدرس)

### student_grade
تسجيل أكاديمي للطالب في صف (منفصل عن الاشتراك المدفوع)

### roles / permissions (Spatie)
- أدوار: `admin`, `teacher`, `student`, `parent`
- صلاحيات أساسية: students.*, payments.*, academic.manage, content.manage, exams.manage, reports.view, users.manage, settings.manage

---

## الكيانات المخططة (قبل التنفيذ)

```
branches 1──* users
users (teacher) 1──* users (students via created_by)
users *──* roles
parent_student (parent_id, student_id)

stages 1──* grades 1──* subjects 1──* units 1──* lessons
teacher_subject (teacher_id, subject_id)
student_grade (student_id, grade_id)

lessons:
  - type, title, body, ordering
  - bunny_video_id nullable
  - attachments (downloadable flag)

lesson_progress (student_id, lesson_id, percent, completed_at)

questions + question_options
exams + exam_questions
exam_attempts (student_id, exam_id, started_at, submitted_at, score)
exam_answers (attempt_id, question_id, answer_payload, saved_at)  ← autosave

subscription_plans
subscriptions (student_id, subject_id, teacher_id, plan_id, branch_id, status, starts_at, ends_at)

payments:
  - student_id, teacher_id, subscription_id, branch_id
  - channel: vodafone_cash | cash
  - provider: manual (جاهز للتمديد)
  - amount, external_reference, proof_path
  - status: pending_review | confirmed | rejected
  - recorded_by, reviewed_by, reviewed_at, rejection_reason

invoices
notifications
certificates (student_id, exam_id, exam_attempt_id, verification_code, score, issued_at)
```

---

## علاقات حساسة للصلاحيات
- المدرس يرى طلاب مرتبطين بـ `teacher_subject` أو `created_by = teacher`.
- المدرس يراجع `payments` حيث `teacher_id = auth`.
- الأدمن يرى كل السجلات عبر الفروع.
