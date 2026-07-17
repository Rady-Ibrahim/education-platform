<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('meeting_url')->nullable()->after('bunny_video_id');
            $table->timestamp('scheduled_at')->nullable()->after('meeting_url');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'scheduled_at']);
        });
    }
};
