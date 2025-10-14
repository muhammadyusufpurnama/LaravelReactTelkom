<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_upload_data', function (Blueprint $table) {
            // Kolom untuk menyimpan Order ID dari file Excel.
            // Dijadikan primary key untuk mempercepat proses join.
            $table->string('order_id')->primary();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_upload_data');
    }
};
