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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_result_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('product');
            $table->string('sku', 100)->nullable();
            $table->string('link')->nullable();
            $table->string('contact', 100)->nullable();
            $table->decimal('sale_amount', 10, 2)->nullable();
            $table->decimal('product_cost', 10, 2);
            $table->decimal('advertising_cost', 10, 2)->nullable()->default(0.00);
            $table->decimal('shipping_cost', 10, 2)->nullable()->default(0.00);
            $table->decimal('other_costs', 10, 2)->nullable()->default(0.00);
            $table->enum('status', ['in_stock', 'sold'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
