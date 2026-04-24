<?php

use App\Domain\Clinic\Enums\ModuleKey;

test('controlled_drugs depends on inventory', function () {
    expect(ModuleKey::ControlledDrugs->dependsOn())->toContain(ModuleKey::Inventory);
});

test('pos depends on inventory', function () {
    expect(ModuleKey::Pos->dependsOn())->toContain(ModuleKey::Inventory);
});

test('hospitalization depends on patients', function () {
    expect(ModuleKey::Hospitalization->dependsOn())->toContain(ModuleKey::Patients);
});

test('grooming depends on appointments', function () {
    expect(ModuleKey::Grooming->dependsOn())->toContain(ModuleKey::Appointments);
});

test('patients has no dependencies', function () {
    expect(ModuleKey::Patients->dependsOn())->toBeEmpty();
});

test('inventory has no dependencies', function () {
    expect(ModuleKey::Inventory->dependsOn())->toBeEmpty();
});

test('every case has a label and description', function (ModuleKey $key) {
    expect($key->label())->not->toBeEmpty();
    expect($key->description())->not->toBeEmpty();
    expect($key->icon())->not->toBeEmpty();
})->with(ModuleKey::cases());
