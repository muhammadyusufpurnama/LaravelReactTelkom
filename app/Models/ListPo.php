<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListPo extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika berbeda dari 'list_pos'
    protected $table = 'list_po';

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'nipnas',
        'po',
        'segment',
        'bill_city',
        'witel',
    ];
}
