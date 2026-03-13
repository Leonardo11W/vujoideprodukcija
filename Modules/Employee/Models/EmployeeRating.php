<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Modules\Branch\Models\Branch;
use app\Models\Branch;
// use Modules\User\Entities\User;
use App\Models\User;
use Modules\Employee\Models\BranchEmployee;

class EmployeeRating extends Model
{
    use HasFactory;

    protected $table = 'employee_rating';

    protected $fillable = [
        'employee_id',
        'review_msg',
        'rating',
        'user_id',
        'branch_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Employee\Database\factories\BranchEmployeeFactory::new();
    }

    /**
     * Get the branch associated with this rating.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the customer/user who gave the rating.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the employee who was rated.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the branch-employee pivot model if needed.
     */
    public function branchEmployee()
    {
        return $this->belongsTo(BranchEmployee::class);
    }
}
