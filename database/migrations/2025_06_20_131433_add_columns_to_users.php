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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('ref')->after('id')->unique()->nullable();
            $table->string('first_name')->nullable()->after('m_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('gender')->nullable()->after('last_name');
            $table->string('dob')->nullable()->after('gender');
            $table->string('whatsapp_no')->nullable()->after('mobile');
            $table->string('address')->nullable()->after('whatsapp_no');
            $table->string('state')->nullable()->after('address');
            $table->string('district')->nullable()->after('state');
            $table->integer('teaching_exp')->nullable()->after('district');
            $table->json('qualification')->nullable()->after('teaching_exp');
            $table->json('area_of_work')->nullable()->after('qualification');
            $table->string('membership_type')->nullable()->after('area_of_work');
            $table->string('membership_plan')->nullable()->after('membership_type');
            $table->string('pin')->nullable()->after('membership_plan');
        });
    }

};
