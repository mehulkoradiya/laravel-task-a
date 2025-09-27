<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageVariant extends Model
{
        protected $fillable = ['image_id','variant','path','width','height','size_bytes'];
}
