<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('billing_month');
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('status', 16)->default('due');
            $table->timestamps();

            $table->unique(['subscription_id', 'billing_month']);
            $table->index(['teacher_id', 'billing_month', 'status']);
            $table->index(['student_id', 'billing_month']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_charge_id')
                ->nullable()
                ->after('subscription_id')
                ->constrained('subscription_charges')
                ->nullOnDelete();
            $table->string('receipt_number', 32)->nullable()->unique()->after('notes');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_charge_id');
            $table->dropUnique(['receipt_number']);
            $table->dropColumn(['subscription_charge_id', 'receipt_number', 'discount_amount']);
        });

        Schema::dropIfExists('subscription_charges');
    }
};
