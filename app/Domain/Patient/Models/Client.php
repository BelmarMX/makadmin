<?php

namespace App\Domain\Patient\Models;

use App\Contracts\Integrations\MediaStorage;
use App\Support\Tenancy\BelongsToClinic;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Client extends Model implements Auditable
{
    use BelongsToClinic;

    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /** @var list<string> */
    protected $appends = ['avatar', 'initials'];

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'phone',
        'phone_alt',
        'avatar_path',
        'address',
        'colonia',
        'city',
        'state',
        'postal_code',
        'curp',
        'rfc',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->avatar_path
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

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function activePatients(): HasMany
    {
        return $this->hasMany(Patient::class)->where('is_active', true);
    }

    /** @param Builder<Client> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<Client> $query */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $operator = $this->searchOperator();

        return $query->where(function (Builder $builder) use ($term, $operator): void {
            $builder
                ->where('name', $operator, "%{$term}%")
                ->orWhere('email', $operator, "%{$term}%")
                ->orWhere('phone', $operator, "%{$term}%");
        });
    }

    private function searchOperator(): string
    {
        return $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
