<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['uuid','filename','size','checksum','storage_disk','status','total_chunks','received_chunks','metadata'];
    protected $casts = ['metadata' => 'array'];

    public function image() {
        return $this->hasOne(Image::class);
    }
}
