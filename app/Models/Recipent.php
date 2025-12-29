<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipent extends Model
{
    protected $fillable = ['name','slug','url'];

     public function products()
    {
        return $this->hasMany(RecipientProduct::class, 'recipent_id', 'id');
    }
}
