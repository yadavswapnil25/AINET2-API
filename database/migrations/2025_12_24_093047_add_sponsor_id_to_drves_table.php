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
            if (!Schema::hasColumn('drves', 'sponsor_id')) {
                $table->unsignedBigInteger('sponsor_id')->nullable()->after('user_id');
                $table->foreign('sponsor_id')->references('id')->on('sponsors')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drves', function (Blueprint $table) {
            if (Schema::hasColumn('drves', 'sponsor_id')) {
                try {
                    $table->dropForeign(['sponsor_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('sponsor_id');
            }
        });
    }
};
