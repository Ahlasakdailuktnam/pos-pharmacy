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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('name_en')->nullable();

            // Relationship
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('sub_category_id')
                ->nullable()
                ->constrained('sub_categories')
                ->nullOnDelete();

            // Unit
            $table->foreignId('unit_id')->nullable()
                ->constrained('units')
                ->cascadeOnDelete();
            // Price
            $table->decimal('price_per_unit', 10, 2)->nullable();
            $table->decimal('price_per_box', 10, 2)->nullable();
            $table->integer('box_size')->nullable();

            // Cost
            $table->decimal('cost', 10, 2)->nullable();

            //  Stock (IMPORTANT)
            $table->integer('stock_box')->default(0);
            $table->integer('stock_unit')->default(0); // auto only

            // Other
            $table->date('expiry_date')->nullable();
            $table->string('manufacturer')->nullable();
            $table->boolean('prescription_required')->default(false);

            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('product_code')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
