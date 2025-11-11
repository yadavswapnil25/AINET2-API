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
            if (!Schema::hasColumn('drves', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('student_images');
            }
            if (!Schema::hasColumn('drves', 'payment_id')) {
                $table->string('payment_id')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('drves', 'razorpay_order_id')) {
                $table->string('razorpay_order_id')->nullable()->after('payment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drves', function (Blueprint $table) {
            if (Schema::hasColumn('drves', 'razorpay_order_id')) {
                $table->dropColumn('razorpay_order_id');
            }
            if (Schema::hasColumn('drves', 'payment_id')) {
                $table->dropColumn('payment_id');
            }
            if (Schema::hasColumn('drves', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};

