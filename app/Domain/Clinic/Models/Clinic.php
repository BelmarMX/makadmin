<?php

namespace App\Domain\Clinic\Models;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Enums\FiscalRegime;
use App\Models\User;
use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Clinic extends Model implements Auditable
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    protected static function newFactory(): ClinicFactory
    {
        return ClinicFactory::new();
    }

    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'legal_name',
        'commercial_name',
        'rfc',
        'fiscal_regime',
        'tax_address',
        'logo_path',
        'primary_color',
        'responsible_vet_name',
        'responsible_vet_license',
        'contact_phone',
        'contact_email',
        'settings',
        'is_active',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'fiscal_regime' => FiscalRegime::class,
        ];
    }

    /** @var list<string> */
    protected $appends = ['logo_url'];

    protected function subdomainUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => config('branding.scheme').'://'.$this->slug.'.'.config('branding.apex_domain'),
        );
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path
                ? app(MediaStorage::class)->url($this->logo_path)
                : null,
        );
    }

    public function branches(): HasMany
    {
        return $this->hasMany(ClinicBranch::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(ClinicModule::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
