<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_order_products', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index(); // Penanda batch
            $table->string('order_id')->index(); // Tanpa Foreign Key Constraint
            $table->string('product_name');
            $table->decimal('net_price', 15, 2)->default(0);
            $table->string('channel')->nullable();
            $table->string('status_wfm')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_order_products');
    }
};
