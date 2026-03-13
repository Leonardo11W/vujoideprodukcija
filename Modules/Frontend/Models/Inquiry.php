<?php

namespace Modules\Frontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Frontend\Database\factories\InquiryFactory;

class Inquiry extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     */
    protected $table = 'inquiry';

    protected $fillable = ['name', 'email', 'subject', 'message', 'created_by', 'updated_by', 'deleted_by'];

    protected static function newFactory(): InquiryFactory
    {
        //return InquiryFactory::new();
    }
}
