<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus dulu jika ada, biar bersih
        Schema::dropIfExists('temp_upload_data');

        Schema::create('temp_upload_data', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index(); // Wajib untuk Rollback/History
            $table->string('order_id')->index();

            // --- Kolom Data dari Excel (Dibuat Nullable semua agar tidak error saat insert awal) ---
            $table->text('product')->nullable();
            $table->string('segment')->nullable();
            $table->string('status_wfm')->nullable();
            $table->string('channel')->nullable();     // <-- Ini yang bikin error sebelumnya
            $table->string('filter_produk')->nullable();
            $table->string('witel_lama')->nullable(); // Mapping dari kolom 'Witel' di Excel
            $table->text('layanan')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->text('order_status')->nullable();
            $table->string('order_sub_type')->nullable();
            $table->string('order_status_n')->nullable();
            $table->string('nama_witel')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('milestone')->nullable();

            // Harga & Numerik
            $table->decimal('net_price', 15, 2)->default(0);
            $table->boolean('is_template_price')->default(0);

            $table->integer('tahun')->nullable();
            $table->string('telda')->nullable();
            $table->integer('week')->nullable();

            $table->dateTime('order_created_date')->nullable();

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_upload_data');
    }
};
