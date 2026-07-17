<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('student_code', 32)->nullable()->unique()->after('phone');
            $table->foreignId('branch_id')->nullable()->after('student_code')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropUnique(['phone']);
            $table->dropUnique(['student_code']);
            $table->dropColumn(['phone', 'student_code']);
            $table->dropSoftDeletes();
        });
    }
};
