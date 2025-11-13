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
            if (!Schema::hasColumn('drves', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('member');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drves', function (Blueprint $table) {
            if (Schema::hasColumn('drves', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('user_id');
            }
        });
    }
};
