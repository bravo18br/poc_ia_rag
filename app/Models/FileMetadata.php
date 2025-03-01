<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileMetadata extends Model
{
    protected $table = 'files_metadata';

    protected $fillable = [
        'filename',
        'title',
        'author',
        'created_at',
        'updated_at',
        'source'
    ];

    public function embeddings()
    {
        return $this->hasMany(Embedding::class, 'file_id');
    }
}
