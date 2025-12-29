<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategories extends Model
{
     protected $fillable = ['category_id', 'name', 'slug', 'url'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
