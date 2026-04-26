<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Role-Module Access Configuration
    |--------------------------------------------------------------------------
    |
    | Defines which modules are accessible by default for each role.
    | Used when creating a new clinic and when restoring defaults.
    |
    */
    'clinic_admin' => [
        'patients',
        'inventory',
        'controlled_drugs',
        'appointments',
        'pos',
        'grooming',
        'hospitalization',
        'suppliers',
        'notifications',
        'reports',
        'client_portal',
    ],
    'veterinarian' => [
        'patients',
        'inventory',
        'controlled_drugs',
        'appointments',
        'hospitalization',
        'reports',
    ],
    'groomer' => [
        'patients',
        'appointments',
        'grooming',
        'pos',
    ],
    'receptionist' => [
        'patients',
        'appointments',
        'pos',
        'client_portal',
    ],
    'cashier' => [
        'pos',
        'reports',
    ],
];
