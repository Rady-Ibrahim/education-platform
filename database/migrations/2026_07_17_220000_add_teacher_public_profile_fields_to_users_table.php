<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('headline')->nullable()->after('slug');
            $table->text('bio')->nullable()->after('headline');
            $table->boolean('is_publicly_visible')->default(false)->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['slug', 'headline', 'bio', 'is_publicly_visible']);
        });
    }
};
