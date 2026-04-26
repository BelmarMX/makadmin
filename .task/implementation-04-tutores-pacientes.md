# Implementation 04 — Tutores y Pacientes

> **Objetivo:** permitir al personal de la clínica registrar clientes (tutores/dueños) con sus mascotas (pacientes), buscar por microchip o nombre, y ver la ficha completa del paciente con su historial de tutores. Al terminar, cualquier usuario con permiso puede crear un cliente, asignarle mascotas y buscarlas globalmente desde cualquier flujo del sistema.

**Prerrequisitos:** tasks 00, 01, 02 y 03 completadas.

**Tiempo estimado:** 1 día.

**Referencia:** `CLAUDE.md` §6 (multitenancy), §7 (permisos y roles), §8 (arquitectura base), §14 (catálogos base de especie/raza).

---

## 1. Alcance

Dentro:
- CRUD de clientes (tutores): nombre, email, teléfono, dirección, CURP, RFC, notas.
- CRUD de pacientes (mascotas): nombre, especie, raza, sexo, fecha de nacimiento, microchip, color de pelaje, tamaño, peso, temperamento, foto, notas.
- Relación Client → Patients (un tutor principal por mascota en MVP).
- Búsqueda rápida de cliente por email / teléfono / nombre (para usar en formularios de citas, consultas, etc.).
- Búsqueda global de paciente por microchip o nombre.
- Desactivación de clientes y pacientes (soft + is_active), restauración.
- Auditoría de cambios en ambos modelos.
- Foto de mascota vía MediaStorage (disco local, reemplazable por S3 sin cambios de dominio).
- Seeders de clientes y mascotas de prueba en entorno dev.

Fuera:
- Expediente clínico / historial médico → task 05.
- Vacunas y recordatorios → task 05.
- Múltiples tutores por paciente (co-ownership) → task 05 o posterior.
- Portal del cliente → task 22.
- Notificaciones por WhatsApp → task 16.
- Búsqueda con Elasticsearch o full-text avanzado → tarea futura.

---

## 2. Dominio

`app/Domain/Patient/`

```
Patient/
├── Models/
│   ├── Client.php
│   └── Patient.php
├── Actions/
│   ├── CreateClientAction.php
│   ├── UpdateClientAction.php
│   ├── DeactivateClientAction.php
│   ├── RestoreClientAction.php
│   ├── CreatePatientAction.php
│   ├── UpdatePatientAction.php
│   ├── DeactivatePatientAction.php
│   ├── RestorePatientAction.php
│   ├── LinkPatientToClientAction.php
│   └── UploadPatientPhotoAction.php
├── DataTransferObjects/
│   ├── ClientData.php
│   └── PatientData.php
├── Policies/
│   ├── ClientPolicy.php
│   └── PatientPolicy.php
├── Events/
│   ├── ClientCreated.php
│   ├── ClientUpdated.php
│   ├── ClientDeactivated.php
│   ├── PatientCreated.php
│   ├── PatientUpdated.php
│   └── PatientDeactivated.php
├── Enums/
│   ├── PatientSex.php
│   └── PatientSize.php
└── Permissions.php
```

---

## 3. Enums

### 3.1 `PatientSex`

```php
namespace App\Domain\Patient\Enums;

enum PatientSex: string
{
    case Male       = 'male';
    case Female     = 'female';
    case Unknown    = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::Male    => 'Macho',
            self::Female  => 'Hembra',
            self::Unknown => 'No identificado',
        };
    }
}
```

### 3.2 `PatientSize`

```php
namespace App\Domain\Patient\Enums;

enum PatientSize: string
{
    case Mini   = 'mini';
    case Small  = 'small';
    case Medium = 'medium';
    case Large  = 'large';
    case Giant  = 'giant';

    public function label(): string
    {
        return match($this) {
            self::Mini   => 'Mini',
            self::Small  => 'Pequeño',
            self::Medium => 'Mediano',
            self::Large  => 'Grande',
            self::Giant  => 'Gigante',
        };
    }
}
```

---

## 4. Migraciones

### 4.1 `create_clients_table`

```php
Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();

    $table->string('name', 200);
    $table->string('email')->nullable();
    $table->string('phone', 30)->nullable();
    $table->string('phone_alt', 30)->nullable();

    // Dirección
    $table->string('address')->nullable();
    $table->string('colonia', 100)->nullable();
    $table->string('city', 100)->nullable();
    $table->string('state', 100)->nullable();
    $table->string('postal_code', 10)->nullable();

    // Fiscal / identidad (opcional, para facturación futura)
    $table->string('curp', 20)->nullable();
    $table->string('rfc', 13)->nullable();

    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->softDeletes();
    $table->timestamps();

    // Índices para búsqueda
    $table->index(['clinic_id', 'email']);
    $table->index(['clinic_id', 'phone']);
    $table->index(['clinic_id', 'is_active']);
    $table->index(['clinic_id', 'name']); // partial index no disponible en todos los drivers; ilike en query
});
```

### 4.2 `create_patients_table`

```php
Schema::create('patients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
    $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

    // Catálogos de task-02
    $table->foreignId('species_id')->nullable()->constrained('species')->nullOnDelete();
    $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
    $table->foreignId('temperament_id')->nullable()->constrained('temperaments')->nullOnDelete();
    $table->foreignId('coat_color_id')->nullable()->constrained('pelage_colors')->nullOnDelete();

    $table->string('name', 100);
    $table->string('sex')->default('unknown'); // PatientSex enum
    $table->date('birth_date')->nullable();
    $table->string('microchip', 50)->nullable();

    $table->string('size')->nullable();    // PatientSize enum
    $table->decimal('weight_kg', 6, 2)->nullable();

    $table->string('photo_path')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_sterilized')->default(false);
    $table->boolean('is_deceased')->default(false);
    $table->date('deceased_at')->nullable();

    $table->softDeletes();
    $table->timestamps();

    // Índices
    $table->unique(['clinic_id', 'microchip'], 'unique_microchip_per_clinic');
    $table->index(['clinic_id', 'client_id']);
    $table->index(['clinic_id', 'is_active']);
    $table->index(['clinic_id', 'name']);
    $table->index(['clinic_id', 'species_id']);
});
```

> `microchip` es único **por clínica** (no global), ya que distintas clínicas pueden registrar al mismo animal con el mismo chip.

---

## 5. Modelos

### 5.1 `Client`

```php
namespace App\Domain\Patient\Models;

use App\Support\Tenancy\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Client extends Model implements Auditable
{
    use BelongsToClinic;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'phone',
        'phone_alt',
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

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function activePatients(): HasMany
    {
        return $this->hasMany(Patient::class)->where('is_active', true);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
              ->orWhere('email', 'ilike', "%{$term}%")
              ->orWhere('phone', 'ilike', "%{$term}%");
        });
    }
}
```

### 5.2 `Patient`

```php
namespace App\Domain\Patient\Models;

use App\Domain\Catalog\Models\Breed;
use App\Domain\Catalog\Models\PelageColor;
use App\Domain\Catalog\Models\Species;
use App\Domain\Catalog\Models\Temperament;
use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use App\Support\Tenancy\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Patient extends Model implements Auditable
{
    use BelongsToClinic;
    use SoftDeletes;
    use AuditableTrait;

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

    protected $casts = [
        'sex'          => PatientSex::class,
        'size'         => PatientSize::class,
        'birth_date'   => 'date',
        'deceased_at'  => 'date',
        'is_active'    => 'boolean',
        'is_sterilized'=> 'boolean',
        'is_deceased'  => 'boolean',
        'weight_kg'    => 'decimal:2',
    ];

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

    // Helpers

    public function getAgeAttribute(): ?string
    {
        if (!$this->birth_date) {
            return null;
        }

        $diff = $this->birth_date->diff(now());

        if ($diff->y > 0) {
            return "{$diff->y} " . ($diff->y === 1 ? 'año' : 'años');
        }

        if ($diff->m > 0) {
            return "{$diff->m} " . ($diff->m === 1 ? 'mes' : 'meses');
        }

        return "{$diff->d} " . ($diff->d === 1 ? 'día' : 'días');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMicrochip($query, string $chip)
    {
        return $query->where('microchip', $chip);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
              ->orWhere('microchip', 'ilike', "%{$term}%");
        });
    }
}
```

---

## 6. Permisos

```php
namespace App\Domain\Patient;

class Permissions
{
    // Clientes (tutores)
    public const CLIENTS_VIEW       = 'clients.view';
    public const CLIENTS_CREATE     = 'clients.create';
    public const CLIENTS_UPDATE     = 'clients.update';
    public const CLIENTS_DEACTIVATE = 'clients.deactivate';
    public const CLIENTS_RESTORE    = 'clients.restore';

    // Pacientes (mascotas)
    public const PATIENTS_VIEW       = 'patients.view';
    public const PATIENTS_CREATE     = 'patients.create';
    public const PATIENTS_UPDATE     = 'patients.update';
    public const PATIENTS_DEACTIVATE = 'patients.deactivate';
    public const PATIENTS_RESTORE    = 'patients.restore';

    public static function all(): array
    {
        return [
            self::CLIENTS_VIEW,
            self::CLIENTS_CREATE,
            self::CLIENTS_UPDATE,
            self::CLIENTS_DEACTIVATE,
            self::CLIENTS_RESTORE,
            self::PATIENTS_VIEW,
            self::PATIENTS_CREATE,
            self::PATIENTS_UPDATE,
            self::PATIENTS_DEACTIVATE,
            self::PATIENTS_RESTORE,
        ];
    }
}
```

Se asignan en contexto de equipo (Spatie teams = clinic_id). Registrar en `ModulePermissionsSeeder` bajo la clave de módulo `patients`.

---

## 7. DataTransferObjects

### 7.1 `ClientData`

```php
namespace App\Domain\Patient\DataTransferObjects;

use Illuminate\Http\Request;

readonly class ClientData
{
    public function __construct(
        public string  $name,
        public ?string $email,
        public ?string $phone,
        public ?string $phone_alt,
        public ?string $address,
        public ?string $colonia,
        public ?string $city,
        public ?string $state,
        public ?string $postal_code,
        public ?string $curp,
        public ?string $rfc,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:        $request->string('name'),
            email:       $request->string('email') ?: null,
            phone:       $request->string('phone') ?: null,
            phone_alt:   $request->string('phone_alt') ?: null,
            address:     $request->string('address') ?: null,
            colonia:     $request->string('colonia') ?: null,
            city:        $request->string('city') ?: null,
            state:       $request->string('state') ?: null,
            postal_code: $request->string('postal_code') ?: null,
            curp:        $request->string('curp') ?: null,
            rfc:         $request->string('rfc') ?: null,
            notes:       $request->string('notes') ?: null,
        );
    }
}
```

### 7.2 `PatientData`

```php
namespace App\Domain\Patient\DataTransferObjects;

use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class PatientData
{
    public function __construct(
        public string       $name,
        public PatientSex   $sex,
        public ?int         $species_id,
        public ?int         $breed_id,
        public ?int         $temperament_id,
        public ?int         $coat_color_id,
        public ?string      $birth_date,
        public ?string      $microchip,
        public ?PatientSize $size,
        public ?float       $weight_kg,
        public ?string      $notes,
        public bool         $is_sterilized,
        public ?UploadedFile $photo,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:           $request->string('name'),
            sex:            PatientSex::from($request->string('sex', 'unknown')),
            species_id:     $request->integer('species_id') ?: null,
            breed_id:       $request->integer('breed_id') ?: null,
            temperament_id: $request->integer('temperament_id') ?: null,
            coat_color_id:  $request->integer('coat_color_id') ?: null,
            birth_date:     $request->string('birth_date') ?: null,
            microchip:      $request->string('microchip') ?: null,
            size:           $request->filled('size') ? PatientSize::from($request->string('size')) : null,
            weight_kg:      $request->filled('weight_kg') ? (float) $request->input('weight_kg') : null,
            notes:          $request->string('notes') ?: null,
            is_sterilized:  $request->boolean('is_sterilized'),
            photo:          $request->file('photo'),
        );
    }
}
```

---

## 8. Actions

### 8.1 `CreateClientAction`

```php
public function handle(ClientData $data): Client
{
    return DB::transaction(function () use ($data) {
        $client = Client::create([
            ...(array) $data,
            'clinic_id' => current_clinic()->id,
            'is_active' => true,
        ]);

        ClientCreated::dispatch($client, auth()->user());

        return $client;
    });
}
```

### 8.2 `UpdateClientAction`

```php
public function handle(Client $client, ClientData $data): Client
{
    return DB::transaction(function () use ($client, $data) {
        $client->update((array) $data);
        ClientUpdated::dispatch($client, auth()->user());
        return $client->fresh();
    });
}
```

### 8.3 `DeactivateClientAction`

```php
public function handle(Client $client): void
{
    DB::transaction(function () use ($client) {
        $client->update(['is_active' => false]);
        // Desactivar también las mascotas del tutor (cascada lógica)
        $client->patients()->update(['is_active' => false]);
        ClientDeactivated::dispatch($client, auth()->user());
    });
}
```

> La desactivación del tutor desactiva sus mascotas en cascada lógica. Si se restaura el tutor, las mascotas **no** se reactivan automáticamente (el personal debe hacerlo explícitamente para evitar errores).

### 8.4 `CreatePatientAction`

```php
public function handle(PatientData $data, Client $client, UploadPatientPhotoAction $uploader): Patient
{
    return DB::transaction(function () use ($data, $client, $uploader) {
        $photoPath = $data->photo ? $uploader->handle($data->photo, $client->clinic_id) : null;

        $patient = Patient::create([
            'clinic_id'      => $client->clinic_id,
            'client_id'      => $client->id,
            'species_id'     => $data->species_id,
            'breed_id'       => $data->breed_id,
            'temperament_id' => $data->temperament_id,
            'coat_color_id'  => $data->coat_color_id,
            'name'           => $data->name,
            'sex'            => $data->sex,
            'birth_date'     => $data->birth_date,
            'microchip'      => $data->microchip,
            'size'           => $data->size,
            'weight_kg'      => $data->weight_kg,
            'notes'          => $data->notes,
            'is_sterilized'  => $data->is_sterilized,
            'photo_path'     => $photoPath,
            'is_active'      => true,
        ]);

        PatientCreated::dispatch($patient, auth()->user());

        return $patient;
    });
}
```

### 8.5 `UploadPatientPhotoAction`

```php
public function handle(UploadedFile $file, int $clinicId): string
{
    // Usa MediaStorage interface; implementación inicial: disco local public
    return Storage::disk('public')->putFile(
        "patients/{$clinicId}",
        $file
    );
}
```

### 8.6 `LinkPatientToClientAction`

Transfiere la mascota de un tutor a otro dentro de la misma clínica:

```php
public function handle(Patient $patient, Client $newClient): void
{
    // Ambos deben pertenecer a la misma clínica (global scope ya garantiza esto)
    abort_unless($patient->clinic_id === $newClient->clinic_id, 403);

    DB::transaction(function () use ($patient, $newClient) {
        $patient->update(['client_id' => $newClient->id]);
        PatientUpdated::dispatch($patient, auth()->user());
    });
}
```

---

## 9. Policies

### 9.1 `ClientPolicy`

```php
public function viewAny(User $user): bool   { return $user->can('clients.view'); }
public function view(User $user, Client $client): bool { return $user->can('clients.view'); }
public function create(User $user): bool     { return $user->can('clients.create'); }
public function update(User $user, Client $client): bool { return $user->can('clients.update'); }
public function deactivate(User $user, Client $client): bool { return $user->can('clients.deactivate'); }
public function restore(User $user, Client $client): bool { return $user->can('clients.restore'); }
```

### 9.2 `PatientPolicy`

Igual pero con permisos `patients.*`. Registrar ambas en `AuthServiceProvider`.

---

## 10. FormRequests

### 10.1 `StoreClientRequest`

```php
public function authorize(): bool
{
    return $this->user()->can('clients.create');
}

public function rules(): array
{
    return [
        'name'        => ['required', 'string', 'max:200'],
        'email'       => ['nullable', 'email', 'max:200',
                          Rule::unique('clients', 'email')
                              ->where('clinic_id', current_clinic()->id)
                              ->whereNull('deleted_at')],
        'phone'       => ['nullable', 'string', 'max:30'],
        'phone_alt'   => ['nullable', 'string', 'max:30'],
        'address'     => ['nullable', 'string', 'max:300'],
        'colonia'     => ['nullable', 'string', 'max:100'],
        'city'        => ['nullable', 'string', 'max:100'],
        'state'       => ['nullable', 'string', 'max:100'],
        'postal_code' => ['nullable', 'string', 'max:10'],
        'curp'        => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/i'],
        'rfc'         => ['nullable', 'string', 'max:13'],
        'notes'       => ['nullable', 'string', 'max:1000'],
    ];
}
```

> Email único **por clínica**, no globalmente — el mismo tutor puede tener cuenta en más de una clínica.

### 10.2 `UpdateClientRequest`

Igual con excepción del `unique`:

```php
Rule::unique('clients', 'email')
    ->where('clinic_id', current_clinic()->id)
    ->whereNull('deleted_at')
    ->ignore($this->route('client')->id)
```

### 10.3 `StorePatientRequest`

```php
public function authorize(): bool
{
    return $this->user()->can('patients.create');
}

public function rules(): array
{
    return [
        'name'           => ['required', 'string', 'max:100'],
        'sex'            => ['required', Rule::enum(PatientSex::class)],
        'species_id'     => ['nullable', 'integer', 'exists:species,id'],
        'breed_id'       => ['nullable', 'integer', 'exists:breeds,id'],
        'temperament_id' => ['nullable', 'integer', 'exists:temperaments,id'],
        'coat_color_id'  => ['nullable', 'integer', 'exists:pelage_colors,id'],
        'birth_date'     => ['nullable', 'date', 'before_or_equal:today'],
        'microchip'      => ['nullable', 'string', 'max:50',
                             Rule::unique('patients', 'microchip')
                                 ->where('clinic_id', current_clinic()->id)
                                 ->whereNull('deleted_at')],
        'size'           => ['nullable', Rule::enum(PatientSize::class)],
        'weight_kg'      => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
        'notes'          => ['nullable', 'string', 'max:1000'],
        'is_sterilized'  => ['boolean'],
        'photo'          => ['nullable', 'image', 'mimes:png,jpg,webp', 'max:4096'],
    ];
}
```

### 10.4 `UpdatePatientRequest`

Igual con `->ignore($this->route('patient')->id)` en la regla de microchip.

---

## 11. Controllers

### 11.1 `ClientController`

```php
namespace App\Http\Controllers\Clinic;

use App\Domain\Patient\Actions\CreateClientAction;
use App\Domain\Patient\Actions\DeactivateClientAction;
use App\Domain\Patient\Actions\RestoreClientAction;
use App\Domain\Patient\Actions\UpdateClientAction;
use App\Domain\Patient\DataTransferObjects\ClientData;
use App\Domain\Patient\Models\Client;
use App\Http\Requests\Patient\StoreClientRequest;
use App\Http\Requests\Patient\UpdateClientRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()
            ->withCount(['patients' => fn($q) => $q->where('is_active', true)])
            ->latest();

        if ($request->filled('q')) {
            $query->search($request->string('q'));
        }

        if ($request->filled('status')) {
            $request->string('status') === 'active'
                ? $query->active()
                : $query->where('is_active', false);
        }

        return Inertia::render('Clinic/Clients/Index', [
            'clients' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('q', 'status'),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Client::class);

        return Inertia::render('Clinic/Clients/Create');
    }

    public function store(StoreClientRequest $request, CreateClientAction $action)
    {
        $client = $action->handle(ClientData::fromRequest($request));

        return redirect()
            ->route('clinic.clients.show', $client)
            ->with('success', "Tutor {$client->name} registrado.");
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);

        $client->load([
            'patients' => fn($q) => $q->with('species', 'breed')->orderBy('name'),
        ]);

        return Inertia::render('Clinic/Clients/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return Inertia::render('Clinic/Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClientAction $action)
    {
        $action->handle($client, ClientData::fromRequest($request));

        return redirect()
            ->route('clinic.clients.show', $client)
            ->with('success', 'Datos del tutor actualizados.');
    }

    public function deactivate(Client $client, DeactivateClientAction $action)
    {
        $this->authorize('deactivate', $client);

        $action->handle($client);

        return redirect()->back()->with('success', 'Tutor desactivado.');
    }

    public function restore(Client $client, RestoreClientAction $action)
    {
        $this->authorize('restore', $client);

        $action->handle($client);

        return redirect()->back()->with('success', 'Tutor reactivado.');
    }
}
```

### 11.2 `PatientController`

```php
namespace App\Http\Controllers\Clinic;

use App\Domain\Patient\Actions\CreatePatientAction;
use App\Domain\Patient\Actions\DeactivatePatientAction;
use App\Domain\Patient\Actions\RestorePatientAction;
use App\Domain\Patient\Actions\UpdatePatientAction;
use App\Domain\Patient\Actions\UploadPatientPhotoAction;
use App\Domain\Patient\DataTransferObjects\PatientData;
use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use App\Domain\Catalog\Models\Species;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function create(Client $client)
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Clinic/Patients/Create', [
            'client'       => $client->only('id', 'name'),
            'species'      => Species::active()->orderBy('name')->get(['id', 'name']),
            'sexOptions'   => collect(PatientSex::cases())->map(fn($e) => ['value' => $e->value, 'label' => $e->label()]),
            'sizeOptions'  => collect(PatientSize::cases())->map(fn($e) => ['value' => $e->value, 'label' => $e->label()]),
        ]);
    }

    public function store(
        StorePatientRequest $request,
        Client $client,
        CreatePatientAction $action,
        UploadPatientPhotoAction $uploader
    ) {
        $patient = $action->handle(PatientData::fromRequest($request), $client, $uploader);

        return redirect()
            ->route('clinic.patients.show', $patient)
            ->with('success', "{$patient->name} registrado.");
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        $patient->load('client', 'species', 'breed', 'temperament', 'coatColor');

        return Inertia::render('Clinic/Patients/Show', [
            'patient' => $patient->append('age'),
        ]);
    }

    public function edit(Patient $patient)
    {
        $this->authorize('update', $patient);

        $patient->load('client');

        return Inertia::render('Clinic/Patients/Edit', [
            'patient'      => $patient,
            'species'      => Species::active()->orderBy('name')->get(['id', 'name']),
            'sexOptions'   => collect(PatientSex::cases())->map(fn($e) => ['value' => $e->value, 'label' => $e->label()]),
            'sizeOptions'  => collect(PatientSize::cases())->map(fn($e) => ['value' => $e->value, 'label' => $e->label()]),
        ]);
    }

    public function update(
        UpdatePatientRequest $request,
        Patient $patient,
        UpdatePatientAction $action,
        UploadPatientPhotoAction $uploader
    ) {
        $action->handle($patient, PatientData::fromRequest($request), $uploader);

        return redirect()
            ->route('clinic.patients.show', $patient)
            ->with('success', 'Datos del paciente actualizados.');
    }

    public function deactivate(Patient $patient, DeactivatePatientAction $action)
    {
        $this->authorize('deactivate', $patient);

        $action->handle($patient);

        return redirect()->back()->with('success', 'Paciente desactivado.');
    }

    public function restore(Patient $patient, RestorePatientAction $action)
    {
        $this->authorize('restore', $patient);

        $action->handle($patient);

        return redirect()->back()->with('success', 'Paciente reactivado.');
    }
}
```

### 11.3 `ClientSearchController` (API rápido para formularios)

```php
namespace App\Http\Controllers\Clinic\Api;

use App\Domain\Patient\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $term = $request->string('q');

        abort_if(mb_strlen($term) < 2, 422, 'Mínimo 2 caracteres.');

        $results = Client::query()
            ->active()
            ->search($term)
            ->with(['activePatients:id,client_id,name,species_id'])
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($results);
    }
}
```

### 11.4 `PatientSearchController` (búsqueda global por microchip/nombre)

```php
namespace App\Http\Controllers\Clinic\Api;

use App\Domain\Patient\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        $term = $request->string('q');

        abort_if(mb_strlen($term) < 2, 422, 'Mínimo 2 caracteres.');

        $results = Patient::query()
            ->active()
            ->search($term)
            ->with(['client:id,name,phone', 'species:id,name'])
            ->limit(10)
            ->get(['id', 'name', 'microchip', 'client_id', 'species_id', 'photo_path']);

        return response()->json($results);
    }
}
```

---

## 12. Rutas

```php
// routes/clinic.php  (o donde estén las rutas de clínica)

Route::middleware(['auth', 'tenant', 'clinic-access'])->group(function () {

    // Clientes (tutores)
    Route::prefix('clients')->name('clinic.clients.')->group(function () {
        Route::get('/',                [ClientController::class, 'index'])      ->middleware('permission:clients.view')      ->name('index');
        Route::get('/create',          [ClientController::class, 'create'])     ->middleware('permission:clients.create')    ->name('create');
        Route::post('/',               [ClientController::class, 'store'])      ->middleware('permission:clients.create')    ->name('store');
        Route::get('/{client}',        [ClientController::class, 'show'])       ->middleware('permission:clients.view')      ->name('show');
        Route::get('/{client}/edit',   [ClientController::class, 'edit'])       ->middleware('permission:clients.update')    ->name('edit');
        Route::put('/{client}',        [ClientController::class, 'update'])     ->middleware('permission:clients.update')    ->name('update');
        Route::post('/{client}/deactivate', [ClientController::class, 'deactivate'])->middleware('permission:clients.deactivate')->name('deactivate');
        Route::post('/{client}/restore',    [ClientController::class, 'restore'])   ->middleware('permission:clients.restore')   ->name('restore');

        // Pacientes anidados bajo cliente
        Route::get('/{client}/patients/create', [PatientController::class, 'create'])->middleware('permission:patients.create')->name('patients.create');
        Route::post('/{client}/patients',        [PatientController::class, 'store']) ->middleware('permission:patients.create')->name('patients.store');
    });

    // Pacientes (acceso directo)
    Route::prefix('patients')->name('clinic.patients.')->group(function () {
        Route::get('/{patient}',       [PatientController::class, 'show'])      ->middleware('permission:patients.view')     ->name('show');
        Route::get('/{patient}/edit',  [PatientController::class, 'edit'])      ->middleware('permission:patients.update')   ->name('edit');
        Route::put('/{patient}',       [PatientController::class, 'update'])    ->middleware('permission:patients.update')   ->name('update');
        Route::post('/{patient}/deactivate', [PatientController::class, 'deactivate'])->middleware('permission:patients.deactivate')->name('deactivate');
        Route::post('/{patient}/restore',    [PatientController::class, 'restore'])   ->middleware('permission:patients.restore')   ->name('restore');
    });

    // Búsqueda API (usada por autocompletar en citas, consultas, etc.)
    Route::prefix('api')->name('clinic.api.')->group(function () {
        Route::get('/clients/search',  ClientSearchController::class) ->middleware('permission:clients.view') ->name('clients.search');
        Route::get('/patients/search', PatientSearchController::class)->middleware('permission:patients.view')->name('patients.search');
    });
});
```

---

## 13. Frontend (Vue)

```
resources/js/pages/Clinic/Clients/
├── Index.vue        # Listado con búsqueda + filtro activo/inactivo
├── Create.vue       # Formulario de nuevo tutor
├── Show.vue         # Detalle tutor + tab Mascotas (lista con botón agregar)
└── Edit.vue         # Edición de tutor

resources/js/pages/Clinic/Patients/
├── Create.vue       # Formulario nueva mascota (breadcrumb: Tutor > Nueva mascota)
├── Show.vue         # Ficha completa: foto, datos, tutor, especie/raza, signos vitales futuros
└── Edit.vue         # Edición de mascota

resources/js/components/domain/Patient/
├── ClientCard.vue         # Card de tutor: nombre, email, teléfono, contador de mascotas
├── PatientCard.vue        # Card de mascota: foto/avatar especie, nombre, raza, tutor
├── PatientStatusBadge.vue # Chip activo/inactivo/fallecido
├── PatientPhotoUpload.vue # Input de foto con preview y crop básico
├── ClientQuickSearch.vue  # Combobox debounce 300ms para buscar tutor (usa /api/clients/search)
└── PatientQuickSearch.vue # Input microchip/nombre (usa /api/patients/search)
```

### 13.1 `Clinic/Clients/Show.vue` — tabs

```
Tab 1: Datos del tutor (nombre, contacto, dirección, CURP/RFC, notas)
Tab 2: Mascotas (listado cards, botón "Agregar mascota", badge activo/inactivo/fallecido)
```

### 13.2 Reglas UI obligatorias

- Inputs: `InputText` + `FloatLabel` variant="on".
- Select de especie/raza: `Select` + `FloatLabel`. Raza se filtra según especie seleccionada (request a `/api/breeds?species_id=X`).
- Sexo y tamaño: `Select` + `FloatLabel`.
- Fecha de nacimiento: `DatePicker` de PrimeVue.
- Foto de mascota: `PatientPhotoUpload.vue` con preview circular.
- Toast de confirmación en creación/edición/desactivación.
- Botones con `v-ripple`; icon-only con `v-tooltip`.
- Búsqueda con debounce 300 ms en `Index.vue`.
- Estado vacío: mensaje "Aún no hay tutores registrados" + botón "Agregar tutor".
- Texto UI en español latinoamericano. No mostrar claves internas (`is_active`, `PatientSex.male`) — usar etiquetas en español.

---

## 14. Seeders

### 14.1 `ClientSeeder`

En entorno `dev/local`:

```php
// Crea 5 clientes por clínica demo
Client::factory()
    ->count(5)
    ->for($clinic)
    ->create()
    ->each(function (Client $client) {
        // 1-3 mascotas por tutor
        Patient::factory()
            ->count(fake()->numberBetween(1, 3))
            ->for($client)
            ->create(['clinic_id' => $client->clinic_id]);
    });
```

Factories deben respetar `clinic_id`.

---

## 15. Tests obligatorios

`tests/Feature/Patient/`:

- `CreateClientTest.php`
  - Happy path: tutor creado, retorna redirect a show.
  - Email duplicado dentro de la misma clínica → error de validación.
  - Email duplicado en otra clínica → permitido.
  - Sin nombre → error de validación.
  - CURP con formato inválido → error de validación.

- `UpdateClientTest.php`
  - Datos actualizados correctamente.
  - Email cambiado a uno existente (misma clínica) → error.
  - Rol sin permiso `clients.update` → 403.

- `DeactivateClientTest.php`
  - Tutor desactivado → mascotas también desactivadas.
  - Restaurar tutor → mascotas siguen inactivas (no restauración automática).

- `ClientTenancyTest.php`
  - Usuario de clínica A no puede ver listado de clínica B.
  - Usuario de clínica A con ID de cliente de clínica B → 404 por global scope.
  - Usuario de clínica A no puede editar cliente de clínica B.

- `CreatePatientTest.php`
  - Happy path: mascota creada con cliente correcto.
  - Microchip duplicado dentro de la misma clínica → error.
  - Microchip duplicado en otra clínica → permitido.
  - `species_id` de otra clínica → validación falla (o acepta si catálogo es global).
  - Subida de foto: almacenada en path correcto.

- `PatientTenancyTest.php`
  - Paciente de clínica B → 404 cuando clínica A lo intenta ver.

- `PatientMicrochipSearchTest.php`
  - Buscar por microchip exacto → retorna paciente correcto.
  - Buscar microchip de otra clínica → no aparece en resultados.
  - Término de búsqueda < 2 chars → 422.

- `ClientSearchTest.php`
  - Buscar por email parcial → retorna clientes de la clínica.
  - Buscar clientes de otra clínica → no aparecen.

`tests/Unit/Patient/`:

- `ClientModelTest.php`
  - Scope `active()`, scope `search()`.
  - Relación `patients()` retorna solo mascotas del cliente.

- `PatientModelTest.php`
  - Accessor `age` retorna valor correcto para distintas fechas.
  - Scope `byMicrochip()`, `search()`.
  - Cast `sex` retorna `PatientSex` enum.

---

## 16. Criterios de aceptación

- [ ] `php artisan migrate:fresh --seed` — ClientSeeder crea tutores y mascotas de prueba sin errores.
- [ ] Admin de clínica accede a `/clients` y ve únicamente los tutores de su clínica.
- [ ] Crear tutor con todos los campos → redirect a detalle con toast de éxito.
- [ ] Detalle de tutor muestra tab "Mascotas" con las mascotas asignadas.
- [ ] Desde el detalle del tutor, crear nueva mascota → mascota queda ligada al tutor.
- [ ] Editar datos del tutor → cambios persisten correctamente.
- [ ] Búsqueda en `/clients?q=mario` → filtra por nombre, email y teléfono.
- [ ] Búsqueda de mascota por microchip vía `/api/patients/search?q={chip}` → retorna solo de la clínica actual.
- [ ] Email de tutor único por clínica (duplicado → error de validación claro).
- [ ] Microchip de mascota único por clínica (duplicado → error de validación claro).
- [ ] Desactivar tutor → tutor y sus mascotas quedan inactivos.
- [ ] Usuario de clínica A **no puede** acceder a datos de clínica B (404).
- [ ] Usuario sin permiso `clients.create` no ve botón "Agregar tutor" ni puede POSTear (403).
- [ ] Foto de mascota sube y se muestra en ficha del paciente.
- [ ] `php artisan test --parallel` — todos los tests pasan.
- [ ] `vendor/bin/pint --test` — sin errores de estilo.
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G` — nivel 6 sin errores.
- [ ] `npm run build && npm run typecheck` — sin errores de tipos.

---

## 17. Siguiente paso

Al completar task 04, habilita:
- **Task 05** (expediente clínico) — necesita `Patient` con ID estable.
- **Task 10** (agenda de citas) — las citas se crean sobre `Client` + `Patient`.

---

## 18. Resultado

- Migraciones creadas:
  - `create_clients_table`
  - `create_patients_table`
- Modelos creados:
  - `App\Domain\Patient\Models\Client`
  - `App\Domain\Patient\Models\Patient`
- Actions implementadas:
  - `CreateClientAction`
  - `UpdateClientAction`
  - `DeactivateClientAction`
  - `RestoreClientAction`
  - `ListClientsAction`
  - `CreatePatientAction`
  - `UpdatePatientAction`
  - `DeactivatePatientAction`
  - `RestorePatientAction`
  - `LinkPatientToClientAction`
  - `UploadPatientPhotoAction`
- Policies registradas:
  - `ClientPolicy`
  - `PatientPolicy`
  - Registro agregado en `AuthServiceProvider`
- Rutas agregadas:
  - CRUD web de `clients`
  - CRUD web de `patients`
  - flujo `clinic.patients.quick-create` / `clinic.patients.quick-store` para alta rápida
  - búsquedas rápidas `clinic.api.clients.search` y `clinic.api.patients.search`
  - endpoints `clinic.api.catalog.municipalities.store` y `clinic.api.catalog.pelage-colors.store` para alta inline desde formularios
  - navegación protegida con `['tenant', 'auth', 'clinic-access']`, `module:patients` y permisos `clients.*` / `patients.*`
- Páginas Vue creadas:
  - `resources/js/pages/Clinic/Clients/Index.vue`
  - `resources/js/pages/Clinic/Clients/Create.vue`
  - `resources/js/pages/Clinic/Clients/Edit.vue`
  - `resources/js/pages/Clinic/Clients/Show.vue`
  - `resources/js/pages/Clinic/Patients/Create.vue`
  - `resources/js/pages/Clinic/Patients/Edit.vue`
  - `resources/js/pages/Clinic/Patients/Show.vue`
  - `resources/js/pages/Clinic/Patients/QuickCreate.vue`
- Componentes creados:
  - `ClientCard.vue`
  - `ClientAvatarUpload.vue`
  - `ClientLocationFields.vue`
  - `PatientCard.vue`
  - `PatientStatusBadge.vue`
  - `PatientPhotoUpload.vue`
  - `ClientQuickSearch.vue`
  - `PatientQuickSearch.vue`
  - `InlineCatalogCreateDialog.vue`
- Tests agregados:
  - `tests/Feature/Patient/CreateClientTest.php`
  - `tests/Feature/Patient/DeactivateClientTest.php`
  - `tests/Feature/Patient/ClientTenancyTest.php`
  - `tests/Feature/Patient/CreatePatientTest.php`
  - `tests/Feature/Patient/QuickCreatePatientTest.php`
  - `tests/Feature/Patient/PatientCatalogTest.php`
  - `tests/Feature/Patient/PatientSearchTest.php`
  - `tests/Unit/Patient/ClientModelTest.php`
  - `tests/Unit/Patient/PatientModelTest.php`
- Seeders y factories agregados:
  - `database/factories/ClientFactory.php`
  - `database/factories/PatientFactory.php`
  - `database/seeders/ClientSeeder.php`
  - `DatabaseSeeder` actualizado para incluir `ClientSeeder`
- Decisiones de diseño:
  - `Client` y `Patient` se desactivan con `is_active = false` y además se aplican `soft deletes` reales.
  - Para soportar restauración, `show`, `edit`, `update`, `deactivate` y `restore` cargan explícitamente con `withTrashed()` en controllers en lugar de depender sólo del route model binding.
  - Se mantuvo `Wayfinder` para todas las rutas del módulo y se añadió entrada de navegación en `AppSidebar`.
  - Se reutilizó `MediaStorage` para foto de paciente y se expuso `photo_url` desde el modelo.
  - Se añadió avatar de tutor con fallback a iniciales y recorte consistente con el flujo existente de usuarios.
  - El formulario de tutores ahora usa `Código postal -> Estado -> Ciudad/Municipio -> Colonia` con carga dinámica desde `postal_codes`; los municipios admiten alta inline por clínica sin romper el catálogo global existente.
  - El flujo de pacientes incorpora `Alta rápida de pacientes` con selector autocomplete de tutor y alta inline de colores de pelaje por clínica.
  - Se mantuvo la UI en español latinoamericano con PrimeVue (`FloatLabel`, `Select`, `CascadeSelect`, `Checkbox`, `Chip`) y buscadores con debounce de 300 ms.
- Verificaciones ejecutadas:
  - `vendor/bin/pint --dirty --format agent` ✅
  - `vendor/bin/pint --test` ✅
  - `php artisan test --compact tests/Feature/Patient tests/Unit/Patient` ✅ (`15 tests`, `51 assertions`)
  - `php artisan test --parallel` ✅ (`166 passed`, `1 skipped`)
  - `php artisan wayfinder:generate --no-interaction` ✅
  - `npm run build` ✅
  - `npm run typecheck` ⚠️ sigue fallando por errores preexistentes en auth/settings con `.form` sobre Wayfinder, fuera de este módulo
- Issues pendientes:
  - No se reconfirmó `php artisan migrate` contra PostgreSQL de desarrollo dentro de esta sesión porque la conexión directa por sandbox ya había fallado previamente al intentar alcanzar `127.0.0.1:5432`.
  - `phpstan` del módulo quedó lanzado, pero esta sesión no recibió salida final concluyente antes del cierre.
