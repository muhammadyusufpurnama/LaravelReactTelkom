<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('list_po', function (Blueprint $table) {
            $table->id();
            $table->string('nipnas')->nullable();
            $table->string('po');
            $table->string('segment')->nullable();
            $table->string('bill_city')->nullable();
            $table->string('witel')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_po');
    }
};
