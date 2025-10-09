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
        Schema::create('user_table_configurations', function (Blueprint $table) {
            $table->id();
            // [TAMBAHKAN BARIS INI]
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->string('page_name')->unique(); // Kunci unik, misal: 'analysis_digital_sme'
            $table->json('configuration'); // Menyimpan seluruh objek/array config sebagai JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_table_configurations');
    }
};
