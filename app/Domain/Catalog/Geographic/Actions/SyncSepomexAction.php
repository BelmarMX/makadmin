<?php

namespace App\Domain\Catalog\Geographic\Actions;

use App\Domain\Catalog\Geographic\Models\Municipality;
use App\Domain\Catalog\Geographic\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;

class SyncSepomexAction
{
    private int $inserted = 0;

    private int $unmatched = 0;

    /** @var array<string, int> */
    private array $stateCache = [];

    /** @var array<string, int> */
    private array $municipalityCache = [];

    public function handle(string $filePath, bool $truncate, OutputInterface $output): void
    {
        if ($truncate) {
            DB::table('postal_codes')->truncate();
        }

        $this->preloadCache();

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $lines = [];
        $isFirst = true;

        while (($line = fgets($handle)) !== false) {
            if ($isFirst) {
                $isFirst = false;

                continue; // skip header
            }

            $line = mb_convert_encoding(trim($line), 'UTF-8', 'ISO-8859-1');
            $cols = explode('|', $line);

            if (count($cols) < 5) {
                continue;
            }

            $row = $this->parseLine($cols);

            if ($row === null) {
                $this->unmatched++;

                continue;
            }

            $lines[] = $row;

            if (count($lines) >= 1000) {
                DB::table('postal_codes')->insert($lines);
                $this->inserted += count($lines);
                $lines = [];
                $output->write('.');
            }
        }

        if ($lines !== []) {
            DB::table('postal_codes')->insert($lines);
            $this->inserted += count($lines);
        }

        fclose($handle);

        Log::info("SyncSepomex: {$this->inserted} rows inserted, {$this->unmatched} unmatched.");

        if ($this->unmatched > 0) {
            Log::channel('single')->warning("SyncSepomex: {$this->unmatched} rows with unmatched municipality/state.");
        }
    }

    public function getInserted(): int
    {
        return $this->inserted;
    }

    public function getUnmatched(): int
    {
        return $this->unmatched;
    }

    private function preloadCache(): void
    {
        State::all(['id', 'name'])->each(fn ($s) => $this->stateCache[mb_strtolower($s->name)] = $s->id);
        Municipality::all(['id', 'state_id', 'name'])->each(
            fn ($m) => $this->municipalityCache["{$m->state_id}:".mb_strtolower($m->name)] = $m->id,
        );
    }

    /**
     * @param array<int, string> $cols
     * @return array<string, mixed>|null
     */
    private function parseLine(array $cols): ?array
    {
        // SEPOMEX format: d_codigo|d_asenta|d_tipo_asenta|D_mnpio|d_estado|...
        $code = trim($cols[0]);
        $settlement = trim($cols[1]);
        $settlementType = trim($cols[2] ?? '');
        $municipalityName = mb_strtolower(trim($cols[3] ?? ''));
        $stateName = mb_strtolower(trim($cols[4] ?? ''));

        $stateId = $this->stateCache[$stateName] ?? null;

        if ($stateId === null) {
            Log::channel('single')->debug("SyncSepomex unmatched state: {$stateName}");

            return null;
        }

        $municipalityId = $this->municipalityCache["{$stateId}:{$municipalityName}"] ?? null;

        if ($municipalityId === null) {
            Log::channel('single')->debug("SyncSepomex unmatched municipality: {$municipalityName} (state_id={$stateId})");

            return null;
        }

        $now = now()->toDateTimeString();

        return [
            'code' => $code,
            'state_id' => $stateId,
            'municipality_id' => $municipalityId,
            'settlement' => $settlement,
            'settlement_type' => $settlementType ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
