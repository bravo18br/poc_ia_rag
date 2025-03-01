<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusRAG extends Model
{
    protected $fillable = [
        'file_path',
        'percent',
        'status',
    ];
}
