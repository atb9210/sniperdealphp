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
        Schema::create('campaign_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('price')->nullable();
            $table->string('location')->nullable();
            $table->string('date')->nullable();
            $table->string('link')->nullable();
            $table->string('image')->nullable();
            $table->string('stato')->default('Disponibile');
            $table->boolean('spedizione')->default(false);
            $table->boolean('notified')->default(false);
            $table->boolean('is_new')->default(true);
            $table->json('extra_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_results');
    }
};
