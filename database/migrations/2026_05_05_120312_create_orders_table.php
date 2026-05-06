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
        Schema::create('orders', function (Blueprint $table) {
             $table->id();

        $table->string('order_number')->unique()->nullable();

        // Customer
        $table->string('customer_name')->nullable();
        $table->string('customer_phone')->nullable();

        // Payment
        $table->enum('payment_method', ['cash', 'card', 'qr']);

        // Cash
        $table->decimal('cash_received', 10, 2)->nullable();
        $table->decimal('change_amount', 10, 2)->nullable();

        // QR
        $table->string('bank_name')->nullable();
        $table->string('payment_phone')->nullable();

        // Money
        $table->decimal('subtotal', 10, 2);
        $table->decimal('discount', 10, 2)->default(0);
        $table->decimal('total', 10, 2);

        // Staff
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
