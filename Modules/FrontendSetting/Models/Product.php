<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'short_description', 'description', 'brand_id', 'unit_id', 'product_tags', 'min_price', 'max_price', 'discount_value'];
}
