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
        Schema::create('staff_details', function (Blueprint $table) {
            $table->id();

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // personal
        $table->string('first_name');
        $table->string('last_name')->nullable();
        $table->string('gender')->nullable();
        $table->string('phone');
        $table->text('address')->nullable();

        // job
        $table->string('employee_id')->unique();
        $table->string('contract_duration')->nullable();
        $table->foreignId('position_id')->constrained();
        $table->string('work_type');
        $table->date('join_date')->nullable();

        // salary
        $table->decimal('base_salary', 10, 2);
        $table->decimal('allowance', 10, 2)->default(0);

        // file
        $table->string('cv_file')->nullable();

        // emergency
        $table->string('emergency_name')->nullable();
        $table->string('emergency_phone')->nullable();

        $table->string('status')->default('active');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_details');
    }
};
