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
        Schema::create('account_officers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_witel');
            $table->string('filter_witel_lama');
            $table->string('special_filter_column')->nullable();
            $table->string('special_filter_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_officers');
    }
};
