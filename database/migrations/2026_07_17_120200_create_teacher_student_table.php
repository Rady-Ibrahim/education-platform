<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['teacher_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_student');
    }
};
