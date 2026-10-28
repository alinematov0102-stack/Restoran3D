<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taom extends Model
{
    use HasFactory;

    protected $table = 'taoms';
    protected $fillable = ['nomi', 'narxi', 'miqdori', 'kategoriya', 'tavsif', 'rasm'];
    protected $casts = ['narxi' => 'decimal:2', 'miqdori' => 'integer'];
}
