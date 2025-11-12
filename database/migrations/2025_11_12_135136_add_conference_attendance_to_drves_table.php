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
        Schema::table('drves', function (Blueprint $table) {
            if (!Schema::hasColumn('drves', 'conference_attendance')) {
                $table->string('conference_attendance')
                    ->nullable()
                    ->after('conference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drves', function (Blueprint $table) {
            if (Schema::hasColumn('drves', 'conference_attendance')) {
                $table->dropColumn('conference_attendance');
            }
        });
    }
};
