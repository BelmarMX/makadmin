<?php

namespace App\Domain\User\Models;

use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use App\Support\Tenancy\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class UserBranchRole extends Model implements Auditable
{
    use BelongsToClinic;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'user_id',
        'branch_id',
        'role',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'branch_id');
    }
}
