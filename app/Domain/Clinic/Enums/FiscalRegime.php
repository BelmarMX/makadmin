<?php

namespace App\Domain\Clinic\Enums;

enum FiscalRegime: string
{
    case GeneralPersonasMorales = '601';
    case PersonasMoralesSinFines = '603';
    case ActividadesEmpresarialesProfesionales = '612';
    case ArrendamientoInmuebles = '606';
    case SinObligacionesFiscales = '616';
    case IncorporacionFiscal = '621';
    case ResimplificadoConfianza = '626';
    case ActividadesAgropecuarias = '622';

    public function label(): string
    {
        return match ($this) {
            self::GeneralPersonasMorales => '601 – General de Ley Personas Morales',
            self::PersonasMoralesSinFines => '603 – Personas Morales con Fines no Lucrativos',
            self::ActividadesEmpresarialesProfesionales => '612 – Personas Físicas con Actividades Empresariales y Profesionales',
            self::ArrendamientoInmuebles => '606 – Arrendamiento',
            self::SinObligacionesFiscales => '616 – Sin Obligaciones Fiscales',
            self::IncorporacionFiscal => '621 – Incorporación Fiscal',
            self::ResimplificadoConfianza => '626 – RESICO (Régimen Simplificado de Confianza)',
            self::ActividadesAgropecuarias => '622 – Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
        };
    }
}
