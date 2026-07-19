<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('delivery_mode', 20)->default('online')->after('is_published');
            $table->decimal('manual_max_score', 8, 2)->nullable()->after('delivery_mode');
            $table->string('paper_path')->nullable()->after('manual_max_score');
            $table->string('paper_disk')->nullable()->after('paper_path');
            $table->string('paper_original_name')->nullable()->after('paper_disk');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_mode',
                'manual_max_score',
                'paper_path',
                'paper_disk',
                'paper_original_name',
            ]);
        });
    }
};
