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
        Schema::table('temp_upload_data', function (Blueprint $table) {
            // Hapus Primary Key yang lama
            $table->dropPrimary('order_id');

            // Tambahkan index biasa pada kolom order_id
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_upload_data', function (Blueprint $table) {
            // Hapus index
            $table->dropIndex(['order_id']);

            // Kembalikan sebagai primary key jika di-rollback
            $table->primary('order_id');
        });
    }
};
