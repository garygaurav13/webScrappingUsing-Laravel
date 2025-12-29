<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientProduct extends Model
{
    protected $fillable = ['recipent_id','title','slug','url','image', 'price','compare_price'];
     public function recipient()
    {
        return $this->belongsTo(Recipent::class, 'recipent_id', 'id');
    }
}
