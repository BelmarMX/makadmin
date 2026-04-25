export const roleLabels: Record<string, string> = {
    clinic_admin: 'Administrador de clínica',
    veterinarian: 'Veterinario',
    groomer: 'Esteticista',
    receptionist: 'Recepcionista',
    cashier: 'Cajero',
};

export const moduleLabels: Record<string, string> = {
    patients: 'Pacientes y tutores',
    inventory: 'Inventario',
    controlled_drugs: 'Medicamentos controlados',
    appointments: 'Agenda y citas',
    pos: 'Punto de venta',
    grooming: 'Estética',
    hospitalization: 'Hospitalización',
    suppliers: 'Proveedores',
    notifications: 'Notificaciones',
    reports: 'Reportes',
    client_portal: 'Portal del cliente',
};

export const permissionActionLabels: Record<string, string> = {
    view: 'Ver',
    create: 'Crear',
    update: 'Editar',
    delete: 'Eliminar',
};

export function roleLabel(value: string) {
    return roleLabels[value] ?? value;
}

export function moduleLabel(value: string) {
    return moduleLabels[value] ?? value;
}
