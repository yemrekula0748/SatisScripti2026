<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('stock', 12, 3)->default(0);
            $table->string('unit')->default('adet');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
