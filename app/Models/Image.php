<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ['upload_id','path','checksum','width','height','size_bytes','metadata'];
    protected $casts = ['metadata' => 'array'];

    public function variants() {
        return $this->hasMany(ImageVariant::class);
    }

    public function upload() {
        return $this->belongsTo(Upload::class);
    }
}
