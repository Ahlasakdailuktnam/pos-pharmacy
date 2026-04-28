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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->string('purchase_code')->unique();

            $table->foreignId('supplier_id')
                  ->constrained('suppliers')
                  ->onDelete('cascade');

            $table->foreignId('warehouse_id')
                  ->constrained('warehouses')
                  ->onDelete('cascade');

            $table->string('invoice_number')->nullable();

            $table->date('purchase_date');

            $table->date('expected_date')->nullable();

            $table->string('payment_method')->default('cash');

            $table->string('payment_status')->default('pending');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->text('note')->nullable();

            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
