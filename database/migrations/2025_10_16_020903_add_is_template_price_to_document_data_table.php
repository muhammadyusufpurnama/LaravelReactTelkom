<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_is_template_price_to_document_data_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_data', function (Blueprint $table) {
            // [TAMBAHKAN] Kolom ini untuk menandai harga template
            // default(false) berarti kita anggap semua data lama memiliki harga valid.
            $table->boolean('is_template_price')->default(false)->after('net_price');
        });
    }

    public function down(): void
    {
        Schema::table('document_data', function (Blueprint $table) {
            $table->dropColumn('is_template_price');
        });
    }
};
