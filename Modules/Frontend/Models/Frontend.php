<?php

namespace Modules\Frontend\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Frontend extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'frontends';

    const CUSTOM_FIELD_MODEL = 'Modules\Frontend\Models\Frontend';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Frontend\database\factories\FrontendFactory::new();
    }
}
