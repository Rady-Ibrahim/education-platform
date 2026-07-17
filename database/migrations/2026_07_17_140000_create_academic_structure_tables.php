<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['stage_id', 'code']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['grade_id', 'code']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teacher_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id']);
        });

        Schema::create('student_grade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grade');
        Schema::dropIfExists('teacher_subject');
        Schema::dropIfExists('units');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('stages');
    }
};
