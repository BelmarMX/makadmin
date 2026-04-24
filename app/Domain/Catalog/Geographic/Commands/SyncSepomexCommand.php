<?php

namespace App\Domain\Catalog\Geographic\Commands;

use App\Domain\Catalog\Geographic\Actions\SyncSepomexAction;
use Illuminate\Console\Command;

class SyncSepomexCommand extends Command
{
    protected $signature = 'catalog:sync-sepomex
        {--file= : Ruta al TXT/CSV de SEPOMEX (pipe-delimited, encoding Latin1)}
        {--truncate : Vaciar tabla postal_codes antes de insertar}';

    protected $description = 'Carga o actualiza el catálogo de códigos postales desde un archivo SEPOMEX';

    public function handle(SyncSepomexAction $action): int
    {
        $file = $this->option('file');

        if (! $file || ! file_exists($file)) {
            $this->error('Archivo no encontrado. Usa --file=ruta/al/archivo.txt');

            return self::FAILURE;
        }

        $truncate = (bool) $this->option('truncate');

        if ($truncate && ! $this->confirm('¿Vaciar la tabla postal_codes antes de insertar?', false)) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $this->info('Sincronizando códigos postales SEPOMEX...');

        $action->handle($file, $truncate, $this->output);

        $this->newLine();
        $this->info("Completado: {$action->getInserted()} filas insertadas, {$action->getUnmatched()} no coincidieron.");

        return self::SUCCESS;
    }
}
