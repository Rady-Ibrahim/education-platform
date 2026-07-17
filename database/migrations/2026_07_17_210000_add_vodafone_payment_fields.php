<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vodafone_cash_number', 32)->nullable()->after('phone');
            $table->text('payment_instructions')->nullable()->after('vodafone_cash_number');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('vodafone_cash_number', 32)->nullable()->after('code');
            $table->text('payment_instructions')->nullable()->after('vodafone_cash_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vodafone_cash_number', 'payment_instructions']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['vodafone_cash_number', 'payment_instructions']);
        });
    }
};
