<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('teacher_groups')->cascadeOnDelete();
            $table->date('session_date');
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'session_date']);
        });

        Schema::create('group_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('group_attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('present');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_attendance_records');
        Schema::dropIfExists('group_attendance_sessions');
    }
};
