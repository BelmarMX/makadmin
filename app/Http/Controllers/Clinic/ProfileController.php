<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\DataTransferObjects\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(string $clinic): Response
    {
        return Inertia::render('Clinic/Profile/Edit', ['user' => auth()->user()]);
    }

    public function update(UpdateProfileRequest $request, string $clinic, UpdateProfileAction $action): RedirectResponse
    {
        $action->handle($request->user(), UserData::fromProfileRequest($request));

        return back()->with('success', 'Perfil actualizado.');
    }
}
