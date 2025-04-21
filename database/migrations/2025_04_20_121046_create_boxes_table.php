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
        Schema::create('boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('original_price', 8, 2);
            $table->decimal('discounted_price', 8, 2);
            $table->string('image')->nullable();
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->string('pickup_time');
            $table->decimal('rating', 3, 1)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boxes');
    }
};
