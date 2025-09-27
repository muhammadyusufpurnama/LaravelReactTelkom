<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DocumentData extends Model
{
    use HasFactory;

    protected $table = 'document_data';

    protected $primaryKey = 'order_id';

    public $incrementing = false;

    protected $fillable = [
        'batch_id', 'order_id', 'product', 'channel', 'filter_produk', 'witel_lama',
        'layanan', 'order_date', 'order_status', 'order_sub_type', 'order_status_n',
        'nama_witel', 'customer_name', 'milestone', 'previous_milestone', 'net_price', 'segment',
        'tahun', 'telda', 'week', 'order_created_date', 'status_wfm','products_processed',
    ];

    protected $casts = [
        'products_processed' => 'boolean',
    ];
}
