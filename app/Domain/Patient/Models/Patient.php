<?php

namespace App\Domain\Patient\Models;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;
use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use App\Support\Tenancy\BelongsToClinic;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Patient extends Model implements Auditable
{
    use BelongsToClinic;

    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /** @var list<string> */
    protected $appends = ['age', 'photo_url'];

    protected static function newFactory(): PatientFactory
    {
        return PatientFactory::new();
    }

    protected $fillable = [
        'clinic_id',
        'client_id',
        'species_id',
        'breed_id',
        'temperament_id',
        'coat_color_id',
        'name',
        'sex',
        'birth_date',
        'microchip',
        'size',
        'weight_kg',
        'photo_path',
        'notes',
        'is_active',
        'is_sterilized',
        'is_deceased',
        'deceased_at',
    ];

    protected function casts(): array
    {
        return [
            'sex' => PatientSex::class,
            'size' => PatientSize::class,
            'birth_date' => 'date',
            'deceased_at' => 'date',
            'is_active' => 'boolean',
            'is_sterilized' => 'boolean',
            'is_deceased' => 'boolean',
            'weight_kg' => 'decimal:2',
        ];
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->birth_date === null) {
                    return null;
                }

                $diff = $this->birth_date->diff(now());

                if ($diff->y > 0) {
                    return $diff->y.' '.($diff->y === 1 ? 'año' : 'años');
                }

                if ($diff->m > 0) {
                    return $diff->m.' '.($diff->m === 1 ? 'mes' : 'meses');
                }

                return $diff->d.' '.($diff->d === 1 ? 'día' : 'días');
            },
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->photo_path
                ? app(MediaStorage::class)->url($this->photo_path)
                : null,
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function temperament(): BelongsTo
    {
        return $this->belongsTo(Temperament::class);
    }

    public function coatColor(): BelongsTo
    {
        return $this->belongsTo(PelageColor::class, 'coat_color_id');
    }

    /** @param Builder<Patient> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<Patient> $query */
    public function scopeByMicrochip(Builder $query, string $microchip): Builder
    {
        return $query->where('microchip', $microchip);
    }

    /** @param Builder<Patient> $query */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $operator = $this->searchOperator();

        return $query->where(function (Builder $builder) use ($term, $operator): void {
            $builder
                ->where('name', $operator, "%{$term}%")
                ->orWhere('microchip', $operator, "%{$term}%");
        });
    }

    private function searchOperator(): string
    {
        return $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
