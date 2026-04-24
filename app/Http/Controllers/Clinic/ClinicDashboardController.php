<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ClinicDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Clinic/Dashboard', [
            'clinic' => current_clinic(),
        ]);
    }
}
