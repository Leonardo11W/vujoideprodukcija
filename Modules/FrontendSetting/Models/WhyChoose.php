<?php

namespace Modules\FrontendSetting\Models;

use Illuminate\Database\Eloquent\Model;

class WhyChoose extends Model
{
    protected $table = 'why_choose';
    protected $fillable = [
        'image',
        'title',
        'subtitle',
        'description',
        'features',
    ];
    protected $casts = [
        'features' => 'array',
    ];

    public function features()
    {
        return $this->hasMany(WhyChooseFeature::class, 'why_choose_id');
    }
} 