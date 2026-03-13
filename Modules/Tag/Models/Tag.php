<?php

namespace Modules\Tag\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Product;

class Tag extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tags';

    const CUSTOM_FIELD_MODEL = 'Modules\Tag\Models\Tag';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Tag\database\factories\TagFactory::new();
    }

    /**
     * Get the products that use this tag.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags', 'tag_id', 'product_id');
    }
}
