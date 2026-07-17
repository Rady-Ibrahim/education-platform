<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 32)->default('text');
            $table->longText('body')->nullable();
            $table->string('bunny_video_id')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'ordering']);
        });

        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_downloadable')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('percent')->default(0);
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('lesson_attachments');
        Schema::dropIfExists('lessons');
    }
};
