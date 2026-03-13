<?php

namespace Modules\Frontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Frontend\Database\factories\UserBranchFactory;

class UserBranch extends Model
{
    use HasFactory;

    protected $table = 'user_branch';


    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'branch_id'];
    
    protected static function newFactory(): UserBranchFactory
    {
        //return UserBranchFactory::new();
    }
}
