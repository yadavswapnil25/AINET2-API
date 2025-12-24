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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drf_id')->nullable();
            $table->integer('rating')->nullable(); // 1-5 star rating
            $table->text('comment')->nullable();
            $table->string('email')->nullable(); // Store email for reference
            $table->string('name')->nullable(); // Store name for reference
            $table->timestamps();

            $table->foreign('drf_id')->references('id')->on('drves')->onDelete('set null');
            $table->index('drf_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
