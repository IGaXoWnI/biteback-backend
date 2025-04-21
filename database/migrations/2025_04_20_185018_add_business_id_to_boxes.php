<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            // Make sure business_id exists and isn't already linked
            if (!Schema::hasColumn('boxes', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable();
            }

            // Add the foreign key if not already present
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });
    }
};
