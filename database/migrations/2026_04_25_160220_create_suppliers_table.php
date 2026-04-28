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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('supplier_code')->unique(); // S001
            $table->string('company_name_kh');
            $table->string('company_name_en')->nullable();

            $table->string('contact_person')->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->text('note')->nullable();

            $table->string('image')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
