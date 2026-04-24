<?php

namespace App\Domain\Clinic\Enums;

enum ModuleKey: string
{
    case Patients = 'patients';
    case Inventory = 'inventory';
    case ControlledDrugs = 'controlled_drugs';
    case Appointments = 'appointments';
    case Pos = 'pos';
    case Grooming = 'grooming';
    case Hospitalization = 'hospitalization';
    case Suppliers = 'suppliers';
    case Notifications = 'notifications';
    case Reports = 'reports';
    case ClientPortal = 'client_portal';

    public function label(): string
    {
        return match ($this) {
            self::Patients => 'Pacientes y Tutores',
            self::Inventory => 'Inventario',
            self::ControlledDrugs => 'Medicamentos Controlados',
            self::Appointments => 'Agenda y Citas',
            self::Pos => 'Punto de Venta',
            self::Grooming => 'Estética',
            self::Hospitalization => 'Hospitalización',
            self::Suppliers => 'Proveedores',
            self::Notifications => 'Notificaciones WhatsApp',
            self::Reports => 'Reportes y Dashboard',
            self::ClientPortal => 'Portal del Cliente',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Patients => 'Gestión de dueños y mascotas, expediente clínico, vacunas.',
            self::Inventory => 'Catálogo de productos y servicios, lotes, caducidades, kárdex.',
            self::ControlledDrugs => 'Registro NOM/COFEPRIS para sustancias controladas (requiere Inventario).',
            self::Appointments => 'Agenda de citas, sala de espera en tiempo real.',
            self::Pos => 'Caja, cortes, pagos (requiere Inventario).',
            self::Grooming => 'Módulo de estética con tiempos por raza (requiere Agenda).',
            self::Hospitalization => 'Tablero de hospitalización y medicación (requiere Pacientes).',
            self::Suppliers => 'Directorio, comparativos y órdenes de compra.',
            self::Notifications => 'Recordatorios y avisos por WhatsApp.',
            self::Reports => 'KPIs, utilidad, segmentación de clientes.',
            self::ClientPortal => 'Portal público para que el dueño vea el expediente de su mascota.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Patients => 'paw-print',
            self::Inventory => 'package',
            self::ControlledDrugs => 'shield-alert',
            self::Appointments => 'calendar',
            self::Pos => 'receipt',
            self::Grooming => 'scissors',
            self::Hospitalization => 'bed',
            self::Suppliers => 'truck',
            self::Notifications => 'message-circle',
            self::Reports => 'bar-chart-2',
            self::ClientPortal => 'user-circle',
        };
    }

    /** @return self[] */
    public function dependsOn(): array
    {
        return match ($this) {
            self::ControlledDrugs => [self::Inventory],
            self::Pos => [self::Inventory],
            self::Grooming => [self::Appointments],
            self::Hospitalization => [self::Patients],
            default => [],
        };
    }
}
