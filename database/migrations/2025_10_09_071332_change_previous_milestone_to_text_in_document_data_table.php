<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_data', function (Blueprint $table) {
            // Mengubah tipe kolom menjadi TEXT
            $table->text('previous_milestone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_data', function (Blueprint $table) {
            // Mengembalikan ke VARCHAR(255) jika di-rollback
            $table->string('previous_milestone')->nullable()->change();
        });
    }
};
