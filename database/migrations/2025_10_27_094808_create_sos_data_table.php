<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_sos_data_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sos_data', function (Blueprint $table) {
            $table->id();
            $table->string('nipnas')->nullable();
            $table->string('standard_name')->nullable();
            $table->string('order_id')->unique()->comment('Order ID unik sebagai primary key');
            $table->string('order_subtype')->nullable();
            $table->text('order_description')->nullable();
            $table->string('segmen')->nullable();
            $table->string('sub_segmen')->nullable();
            $table->string('cust_city')->nullable();
            $table->string('cust_witel')->nullable();
            $table->string('serv_city')->nullable();
            $table->string('service_witel')->nullable();
            $table->string('bill_witel')->nullable()->index(); // Di-index untuk query lebih cepat
            $table->string('li_product_name')->nullable();
            $table->date('li_billdate')->nullable();
            $table->string('li_milestone')->nullable();
            $table->string('kategori')->nullable()->index(); // Di-index untuk filter tab
            $table->string('li_status')->nullable();
            $table->date('li_status_date')->nullable();
            $table->string('is_termin')->nullable();
            $table->decimal('biaya_pasang', 15, 2)->default(0);
            $table->decimal('hrg_bulanan', 15, 2)->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->dateTime('order_created_date')->nullable();
            $table->string('agree_type')->nullable();
            $table->date('agree_start_date')->nullable();
            $table->date('agree_end_date')->nullable();
            $table->integer('lama_kontrak_hari')->default(0);
            $table->string('amortisasi')->nullable();
            $table->string('action_cd')->nullable();
            $table->string('kategori_umur')->nullable()->index(); // Di-index untuk query <3BLN / >3BLN
            $table->integer('umur_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sos_data');
    }
};
