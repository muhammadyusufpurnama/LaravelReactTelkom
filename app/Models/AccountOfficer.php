<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountOfficer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_witel',
        'filter_witel_lama',
        'special_filter_column',
        'special_filter_value',
    ];
}
