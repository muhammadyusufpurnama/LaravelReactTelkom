<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanceledOrder extends Model
{
    use HasFactory;

    // Tentukan nama tabel secara eksplisit
    protected $table = 'canceled_orders';

    // Tentukan primary key dan tipenya (jika bukan 'id' integer)
    protected $primaryKey = 'order_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Kolom yang boleh diisi
    protected $fillable = [
        'order_id',
    ];
}
