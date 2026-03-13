<?php

namespace Modules\FrontendSetting\Models;

use Illuminate\Database\Eloquent\Model;

class WhyChooseFeature extends Model
{
    protected $table = 'why_choose_features';
    protected $fillable = [
        'why_choose_id',
        'title',
        'subtitle',
        'image',
    ];
} 