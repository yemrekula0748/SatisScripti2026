<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProducts extends Command
{
    protected $signature = 'products:import {file} {--company=1 : Company ID to import products into}';
    protected $description = 'Import products from a SQL dump file (parapharmvit format)';

    public function handle(): void
    {
        $file = $this->argument('file');
        $companyId = (int) $this->option('company');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return;
        }

        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company with ID $companyId not found.");
            $this->line("Available companies:");
            Company::all(['id', 'name'])->each(fn($c) => $this->line("  [{$c->id}] {$c->name}"));
            return;
        }

        $this->info("Importing products into: {$company->name}");

        $sql = file_get_contents($file);

        // Extract INSERT statements
        preg_match_all("/INSERT INTO `products` [^;]+;/s", $sql, $matches);

        if (empty($matches[0])) {
            $this->error("No INSERT statements found in file.");
            return;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($matches[0] as $insertSql) {
            // Extract VALUES portion
            preg_match_all("/\(([^)]+)\)/", $insertSql, $rows);

            foreach ($rows[1] as $row) {
                // Parse CSV-like values
                $values = $this->parseValues($row);

                if (count($values) < 7) continue;

                // SQL columns: id, barcode, name, category_id, purchase_price, sale_price, stock_quantity, min_stock, unit, tax_rate, is_active, created_at, updated_at
                $barcode   = trim($values[1] ?? '', "'\" ");
                $name      = trim($values[2] ?? '', "'\" ");
                $salePrice = (float) trim($values[5] ?? 0, "'\" ");
                $stock     = (float) trim($values[6] ?? 0, "'\" ");
                $unit      = trim($values[8] ?? 'adet', "'\" ");
                $isActive  = (int) trim($values[10] ?? 1, "'\" ");

                if (empty($name)) continue;

                // Skip if barcode already exists for this company
                if ($barcode && Product::where('company_id', $companyId)->where('barcode', $barcode)->exists()) {
                    $skipped++;
                    continue;
                }

                Product::create([
                    'company_id' => $companyId,
                    'barcode'    => $barcode ?: null,
                    'name'       => $name,
                    'sale_price' => $salePrice,
                    'stock'      => $stock,
                    'unit'       => in_array($unit, ['adet', 'kg', 'lt', 'mt', 'm', 'paket', 'kutu']) ? $unit : 'adet',
                    'is_active'  => (bool) $isActive,
                ]);

                $imported++;
            }
        }

        $this->info("✓ Imported: $imported products");
        if ($skipped > 0) {
            $this->warn("⚠ Skipped (duplicate barcode): $skipped products");
        }
    }

    private function parseValues(string $row): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];

            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $row[$i-1] !== '\\')) {
                $inString = false;
                $current .= $char;
            } elseif (!$inString && $char === ',') {
                $values[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $values[] = trim($current);
        }

        return $values;
    }
}
