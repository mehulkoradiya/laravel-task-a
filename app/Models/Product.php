<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['sku','name','description','price','stock','metadata','primary_image_id'];
    protected $casts = ['metadata' => 'array'];

    public function primaryImage() {
        return $this->belongsTo(Image::class, 'primary_image_id');
    }
}
