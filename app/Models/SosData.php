<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SosData extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sos_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nipnas',
        'standard_name',
        'order_id',
        'order_subtype',
        'order_description',
        'segmen',
        'sub_segmen',
        'cust_city',
        'cust_witel',
        'serv_city',
        'service_witel',
        'bill_witel',
        'li_product_name',
        'li_billdate',
        'li_milestone',
        'kategori',
        'li_status',
        'li_status_date',
        'is_termin',
        'biaya_pasang',
        'hrg_bulanan',
        'revenue',
        'order_created_date',
        'agree_type',
        'agree_start_date',
        'agree_end_date',
        'lama_kontrak_hari',
        'amortisasi',
        'action_cd',
        'kategori_umur',
        'umur_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'li_billdate' => 'date',
        'li_status_date' => 'date',
        'order_created_date' => 'datetime',
        'agree_start_date' => 'date',
        'agree_end_date' => 'date',
    ];
}
