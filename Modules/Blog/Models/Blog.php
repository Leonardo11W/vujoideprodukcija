<?php

namespace Modules\Blog\Models;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User; // ✅ correct


class Blog extends BaseModel
{
    // use HasFactory;
    use HasFactory;
    use SoftDeletes;


    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title', 'description', 'total_views' , 'author_id' , 'status','is_featured', 'tags'
    ];

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'auther_id', 'id')->withTrashed();
    }

}
