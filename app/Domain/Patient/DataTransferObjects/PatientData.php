<?php

namespace App\Domain\Patient\DataTransferObjects;

use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class PatientData
{
    public function __construct(
        public string $name,
        public PatientSex $sex,
        public ?int $speciesId,
        public ?int $breedId,
        public ?int $temperamentId,
        public ?int $coatColorId,
        public ?string $birthDate,
        public ?string $microchip,
        public ?PatientSize $size,
        public ?float $weightKg,
        public ?string $notes,
        public bool $isSterilized,
        public bool $isDeceased,
        public ?string $deceasedAt,
        public ?UploadedFile $photo,
    ) {}

    public static function fromRequest(FormRequest|Request $request): self
    {
        $validated = method_exists($request, 'validated')
            ? $request->validated()
            : $request->all();

        return new self(
            name: $validated['name'],
            sex: PatientSex::from($validated['sex']),
            speciesId: isset($validated['species_id']) ? (int) $validated['species_id'] : null,
            breedId: isset($validated['breed_id']) ? (int) $validated['breed_id'] : null,
            temperamentId: isset($validated['temperament_id']) ? (int) $validated['temperament_id'] : null,
            coatColorId: isset($validated['coat_color_id']) ? (int) $validated['coat_color_id'] : null,
            birthDate: $validated['birth_date'] ?? null,
            microchip: $validated['microchip'] ?? null,
            size: isset($validated['size']) && $validated['size'] !== null ? PatientSize::from($validated['size']) : null,
            weightKg: isset($validated['weight_kg']) ? (float) $validated['weight_kg'] : null,
            notes: $validated['notes'] ?? null,
            isSterilized: (bool) ($validated['is_sterilized'] ?? false),
            isDeceased: (bool) ($validated['is_deceased'] ?? false),
            deceasedAt: $validated['deceased_at'] ?? null,
            photo: $request->file('photo'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sex' => $this->sex,
            'species_id' => $this->speciesId,
            'breed_id' => $this->breedId,
            'temperament_id' => $this->temperamentId,
            'coat_color_id' => $this->coatColorId,
            'birth_date' => $this->birthDate,
            'microchip' => $this->microchip,
            'size' => $this->size,
            'weight_kg' => $this->weightKg,
            'notes' => $this->notes,
            'is_sterilized' => $this->isSterilized,
            'is_deceased' => $this->isDeceased,
            'deceased_at' => $this->deceasedAt,
        ];
    }
}
