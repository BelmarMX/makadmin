<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Actions\CreateClinicBranchAction;
use App\Domain\Clinic\Actions\UpdateClinicBranchAction;
use App\Domain\Clinic\DataTransferObjects\ClinicBranchData;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use Illuminate\Http\RedirectResponse;

class ClinicBranchController extends Controller
{
    public function store(StoreBranchRequest $request, Clinic $clinic, CreateClinicBranchAction $action): RedirectResponse
    {
        $this->authorize('manageBranches', $clinic);

        $action->handle($clinic, new ClinicBranchData(
            name: $request->validated('name'),
            address: $request->validated('address'),
            phone: $request->validated('phone'),
            isMain: false,
        ));

        return back()->with('success', 'Sucursal creada.');
    }

    public function update(UpdateBranchRequest $request, Clinic $clinic, ClinicBranch $branch, UpdateClinicBranchAction $action): RedirectResponse
    {
        $this->authorize('manageBranches', $clinic);

        $action->handle($branch, new ClinicBranchData(
            name: $request->validated('name'),
            address: $request->validated('address'),
            phone: $request->validated('phone'),
            isMain: $branch->is_main,
        ));

        return back()->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Clinic $clinic, ClinicBranch $branch): RedirectResponse
    {
        $this->authorize('manageBranches', $clinic);
        $this->authorize('delete', $branch);

        $branch->delete();

        return back()->with('success', 'Sucursal archivada.');
    }
}
