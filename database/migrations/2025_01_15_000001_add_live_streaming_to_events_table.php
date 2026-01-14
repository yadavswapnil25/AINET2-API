<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_live')->default(false)->after('is_active');
            $table->enum('stream_type', ['youtube', 'facebook', 'zoom', 'custom', 'embed'])->nullable()->after('is_live');
            $table->string('stream_url')->nullable()->after('stream_type');
            $table->text('embed_code')->nullable()->after('stream_url');
            $table->string('stream_id')->nullable()->after('embed_code'); // For YouTube/Facebook video IDs
            $table->string('banner_image')->nullable()->after('stream_id');
            $table->string('guest_speaker')->nullable()->after('banner_image');
            $table->text('topic_description')->nullable()->after('guest_speaker');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'is_live',
                'stream_type',
                'stream_url',
                'embed_code',
                'stream_id',
                'banner_image',
                'guest_speaker',
                'topic_description'
            ]);
        });
    }
};

