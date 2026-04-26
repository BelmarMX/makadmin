<?php

namespace App\Models;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\User\Models\UserBranchPermission;
use App\Domain\User\Models\UserBranchRole;
use App\Support\Tenancy\BelongsToClinic;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    use BelongsToClinic;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Impersonate;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'clinic_id',
        'branch_id',
        'is_super_admin',
        'phone',
        'avatar_path',
        'professional_license',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canImpersonate(): bool
    {
        return $this->is_super_admin;
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->is_super_admin;
    }

    /** @var list<string> */
    protected $appends = ['avatar'];

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar_path
                ? app(MediaStorage::class)->url($this->avatar_path)
                : null,
        );
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: fn (): string => str($this->name)
                ->explode(' ')
                ->filter()
                ->map(fn (string $word): string => mb_substr($word, 0, 1))
                ->join(''),
        )->shouldCache();
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'branch_id');
    }

    public function branchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class);
    }

    public function userBranchPermissions(): HasMany
    {
        return $this->hasMany(UserBranchPermission::class);
    }

    /** @param Builder<User> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<User> $query */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /** @param Builder<User> $query */
    public function scopeByBranch(Builder $query, ClinicBranch $branch): Builder
    {
        return $query->where('branch_id', $branch->id);
    }

    /** @param Builder<User> $query */
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->role($role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function isClinicAdmin(): bool
    {
        return $this->hasRole('clinic_admin');
    }

    public function isVeterinarian(): bool
    {
        return $this->hasRole('veterinarian');
    }

    public function isGroomer(): bool
    {
        return $this->hasRole('groomer');
    }

    public function isReceptionist(): bool
    {
        return $this->hasRole('receptionist');
    }

    public function isCashier(): bool
    {
        return $this->hasRole('cashier');
    }
}
