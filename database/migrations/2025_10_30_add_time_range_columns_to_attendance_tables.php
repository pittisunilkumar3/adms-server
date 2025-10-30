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
        // Add columns to staff_attendance table
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->integer('time_range_id')->nullable()->after('is_authorized_range');
            $table->time('check_in_time')->nullable()->after('time_range_id');
            $table->time('check_out_time')->nullable()->after('check_in_time');
        });

        // Add columns to student_attendences table
        Schema::table('student_attendences', function (Blueprint $table) {
            $table->integer('time_range_id')->nullable()->after('is_authorized_range');
            $table->time('check_in_time')->nullable()->after('time_range_id');
            $table->time('check_out_time')->nullable()->after('check_in_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from staff_attendance table
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->dropColumn(['time_range_id', 'check_in_time', 'check_out_time']);
        });

        // Remove columns from student_attendences table
        Schema::table('student_attendences', function (Blueprint $table) {
            $table->dropColumn(['time_range_id', 'check_in_time', 'check_out_time']);
        });
    }
};

