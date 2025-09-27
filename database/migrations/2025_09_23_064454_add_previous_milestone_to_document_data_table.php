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
        Schema::table('document_data', function (Blueprint $table) {
            // Kolom untuk menyimpan milestone lama sebelum di-update
            $table->string('previous_milestone')->nullable()->after('milestone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_data', function (Blueprint $table) {
            //
        });
    }
};
