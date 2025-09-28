<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use League\Csv\Reader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportProductsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    protected string $importId;
    protected string $filePath;

    
    /**
     * Create a new job instance.
     */
    public function __construct($importId, $filePath) {
        $this->importId = $importId;
        // Use the file path as provided
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $summary = ['status'=>'running','total'=>0,'imported'=>0,'updated'=>0,'invalid'=>0,'duplicates'=>0,'errors'=>[]];
        Cache::put("product_import:{$this->importId}", $summary, now()->addHours(2));

        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $batch = [];
        $seen = [];
        $batchSize = 500;

        foreach ($records as $offset => $record) {
            $summary['total']++;
            if (empty($record['sku'])) {
                $summary['invalid']++;
                $summary['errors'][] = "Row {$offset} missing sku";
                continue;
            }
            if (isset($seen[$record['sku']])) {
                $summary['duplicates']++;
                continue;
            }
            $seen[$record['sku']] = true;
            $batch[] = $record;
            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $summary);
                $batch = [];
                Cache::put("product_import:{$this->importId}", $summary, now()->addHours(2));
            }
        }

        if (count($batch)) $this->processBatch($batch, $summary);

        $summary['status'] = 'completed';
        Cache::put("product_import:{$this->importId}", $summary, now()->addHours(2));
    }

    protected function processBatch(array $batch, array &$summary) 
    {
        $skus = array_column($batch, 'sku');
        $existing = Product::whereIn('sku', $skus)->get()->keyBy('sku');

        $toInsert = [];
        $toUpdate = [];

        foreach ($batch as $r) {
            $sku = $r['sku'];
            $data = [
                'sku' => $sku,
                'name' => $r['name'] ?? null,
                'description' => $r['description'] ?? null,
                'price' => isset($r['price']) ? (float)$r['price'] : 0,
                'stock' => isset($r['stock']) ? (int)$r['stock'] : 0,
                'metadata' => json_encode([]),
            ];
            if ($existing->has($sku)) {
                $toUpdate[$sku] = $data;
            } else {
                $toInsert[] = $data;
            }
        }

        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 200) as $chunk) {
                // Ensure metadata is JSON for each insert
                foreach ($chunk as &$row) {
                    if (is_array($row['metadata'])) {
                        $row['metadata'] = json_encode($row['metadata']);
                    }
                }
                unset($row);
                Product::insert($chunk);
                $summary['imported'] += count($chunk);
            }
        }

        if (!empty($toUpdate)) {
            foreach ($toUpdate as $sku => $data) {
                if (is_array($data['metadata'])) {
                    $data['metadata'] = json_encode($data['metadata']);
                }
                Product::where('sku', $sku)->update($data);
                $summary['updated']++;
            }
        }
    }
}
