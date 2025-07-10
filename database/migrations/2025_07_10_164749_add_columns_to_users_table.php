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
            $table->boolean('has_member_any')->default(0)->after('membership_type');
            $table->string('name_association')->nullable()->after('has_member_any');
            $table->string('expectation')->nullable()->after('name_association');
            $table->boolean('has_newsletter')->default(0)->after('expectation');
            $table->string('title')->nullable()->after('has_newsletter');
            $table->string('address_institution')->nullable()->after('title');
            $table->string('name_institution')->nullable()->after('address_institution');
            $table->string('type_institution')->nullable()->after('name_institution');
            $table->string('other_institution')->nullable()->after('type_institution');
            $table->string('contact_person')->nullable()->after('other_institution');

        });
    }

};
