<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 32)->default('books');
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('status', 16)->default('due');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teacher_id', 'status']);
            $table->index(['student_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('student_fee_id')
                ->nullable()
                ->after('subscription_charge_id')
                ->constrained('student_fees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_fee_id');
        });

        Schema::dropIfExists('student_fees');
    }
};
