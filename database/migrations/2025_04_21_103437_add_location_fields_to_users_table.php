<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('latitude')->nullable()->after('remember_token');
            $table->string('longitude')->nullable()->after('latitude');
            $table->string('zone')->nullable()->after('longitude');
            $table->string('address')->nullable()->after('zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('zone');
            $table->dropColumn('address');
        });
    }
};
