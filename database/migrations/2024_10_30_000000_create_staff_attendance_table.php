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
        Schema::create('staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('staff_id');
            $table->integer('staff_attendance_type_id');
            $table->tinyInteger('biometric_attendence')->default(0);
            $table->tinyInteger('is_authorized_range')->default(1)->comment('1=Authorized, 0=Unauthorized time range');
            $table->text('biometric_device_data')->nullable();
            $table->string('remark', 200)->default('');
            $table->integer('is_active')->default(1);
            $table->dateTime('created_at');
            $table->date('updated_at')->nullable();

            // Add indexes for better query performance
            $table->index('staff_id');
            $table->index('date');
            $table->index(['staff_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendance');
    }
};

