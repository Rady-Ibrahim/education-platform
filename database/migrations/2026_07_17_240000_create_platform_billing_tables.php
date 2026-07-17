<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('vodafone_cash_number')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->unsignedInteger('trial_days')->default(90);
            $table->decimal('monthly_fee', 10, 2)->default(200);
            $table->unsignedInteger('period_days')->default(30);
            $table->timestamps();
        });

        Schema::create('platform_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('trialing');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('teacher_id');
        });

        Schema::create('platform_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('platform_subscription_id')->constrained('platform_subscriptions')->cascadeOnDelete();
            $table->string('channel')->default('vodafone_cash');
            $table->string('provider')->default('manual');
            $table->decimal('amount', 10, 2);
            $table->string('external_reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status')->default('pending_review');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payments');
        Schema::dropIfExists('platform_subscriptions');
        Schema::dropIfExists('platform_billing_settings');
    }
};
