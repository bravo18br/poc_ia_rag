<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use PgVector\Laravel\Vector;

class Embedding extends Model
{
    use HasNeighbors;

    protected $fillable = ['content', 'embedding', 'file_id'];

    protected $casts = ['embedding' => Vector::class];

    public function file()
    {
        return $this->belongsTo(FileMetadata::class, 'file_id');
    }
}
