<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['collection_id','title','slug','url','image', 'price','compare_price'];
    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
