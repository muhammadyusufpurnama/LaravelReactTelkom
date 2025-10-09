<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTableConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'page_name',
        'configuration',
    ];

    protected $casts = [
        'configuration' => 'array', // Otomatis cast dari/ke JSON
    ];
}
