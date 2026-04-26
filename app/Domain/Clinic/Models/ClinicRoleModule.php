<?php

namespace App\Domain\Clinic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ClinicRoleModule extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = ['clinic_id', 'role', 'module_key', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return list<string>
     */
    public static function enabledModulesForRole(int $clinicId, string $role): array
    {
        $rows = self::query()
            ->where('clinic_id', $clinicId)
            ->where('role', $role)
            ->get();

        if ($rows->isEmpty()) {
            return ClinicModule::withoutGlobalScopes()
                ->where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->pluck('module_key')
                ->all();
        }

        return $rows->where('is_enabled', true)->pluck('module_key')->all();
    }
}
