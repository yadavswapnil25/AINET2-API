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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('location')->nullable(); // e.g., "MUMBAI"
            $table->string('publisher_name'); // e.g., "TOI", "The Times of India"
            $table->string('publisher_logo_path')->nullable(); // Publisher logo image
            $table->string('conference_name')->nullable(); // e.g., "'TH AINET INTERNATIONAL CONFERENCE'"
            $table->string('title'); // Article title
            $table->text('summary'); // Article summary/body
            $table->boolean('has_video')->default(false); // Show video icon
            $table->integer('view_count')->default(0); // View count
            $table->string('link_url')->nullable(); // Link to full article
            $table->date('published_date')->nullable(); // Published date
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
