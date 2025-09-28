<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\ImportProductsJob;

class ImportUpsertTest extends TestCase 
{
    use RefreshDatabase;

    public function test_csv_upsert_creates_and_updates() {

        // prepare CSV content
        $csv = <<<CSV
            sku,name,price,stock
            SKU1,Product 1,10.00,5
            SKU2,Product 2,20.00,3
            CSV;
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $res = $this->postJson('/api/products/import', ['file'=>$file]);
        $res->assertStatus(202);
        $importId = $res->json('import_id');
        $this->assertNotNull($importId);

        // Run the import job manually (simulate queue worker)
        $path = storage_path("app/private/imports/{$importId}.csv");
        $job = new ImportProductsJob($importId, $path);
        $job->handle();

        $this->assertDatabaseHas('products', ['sku'=>'SKU1','name'=>'Product 1']);
        $this->assertDatabaseHas('products', ['sku'=>'SKU2','name'=>'Product 2']);

        // Now update SKU1 via CSV
        $csv2 = <<<CSV
        sku,name,price,stock
        SKU1,Product 1 Updated,15.00,7
        CSV;

        $file2 = UploadedFile::fake()->createWithContent('products2.csv', $csv2);
        $res2 = $this->postJson('/api/products/import', ['file'=>$file2]);
        $importId2 = $res2->json('import_id');

        $job2 = new ImportProductsJob($importId2, storage_path("app/private/imports/{$importId2}.csv"));
        $job2->handle();

        $this->assertDatabaseHas('products', ['sku'=>'SKU1','name'=>'Product 1 Updated','price'=>15.00,'stock'=>7]);
    }
}
