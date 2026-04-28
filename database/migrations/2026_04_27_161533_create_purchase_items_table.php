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
        Schema::create('purchase_items', function (Blueprint $table) {
             $table->id();

            $table->foreignId('purchase_id')
                  ->constrained('purchases')
                  ->onDelete('cascade');

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade');

            $table->integer('qty');

            $table->decimal('unit_cost', 12, 2);

            $table->decimal('discount_percent', 5, 2)->default(0);

            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->decimal('line_total', 12, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
