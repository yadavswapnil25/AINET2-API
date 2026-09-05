<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `event_date` only stores a date, and `starts_at` / `ends_at` are the
     * window during which an event is shown on the website - neither carries
     * the time the event actually runs at. These columns do.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->time('event_time')->nullable()->after('event_date_end');
            $table->time('event_time_end')->nullable()->after('event_time');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_time', 'event_time_end']);
        });
    }
};
