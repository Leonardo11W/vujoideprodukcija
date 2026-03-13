<?php

namespace Modules\FrontendSetting\Models;

use Illuminate\Database\Eloquent\Model;

class VideoSection extends Model
{
    protected $table = 'video_sections';
    protected $fillable = [
        'video_img',
        'video_type',
        'video_url',
        'created_at',
        'updated_at',
    ];
} 