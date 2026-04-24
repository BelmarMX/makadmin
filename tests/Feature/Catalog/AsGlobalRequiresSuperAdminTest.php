<?php

use App\Domain\Catalog\Veterinary\Models\Species;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('asGlobal throws for unauthenticated user', function () {
    expect(fn () => Species::asGlobal())->toThrow(RuntimeException::class);
});

test('asGlobal throws for regular authenticated user', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $this->actingAs($user);

    expect(fn () => Species::asGlobal())->toThrow(RuntimeException::class);
});

test('asGlobal returns builder for super admin', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);

    $builder = Species::asGlobal();

    expect($builder)->toBeInstanceOf(Builder::class);
});
