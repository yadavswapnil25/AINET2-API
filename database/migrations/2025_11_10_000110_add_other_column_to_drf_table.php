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
            if (!Schema::hasColumn('drves', 'other')) {
                $table->string('other')->nullable()->after('areas');
            }
            if (!Schema::hasColumn('drves', 'types')) {
                $table->string('types')->nullable()->after('other');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drves', function (Blueprint $table) {
            if (Schema::hasColumn('drves', 'other')) {
                $table->dropColumn('other');
            }
            if (Schema::hasColumn('drves', 'types')) {
                $table->dropColumn('types');
            }
        });
    }
};

