<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerStaff extends Model
{
    use HasFactory;

    protected $table = 'manager_staff';

    protected $fillable = [
        'manager_id', 'staff_id',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}

