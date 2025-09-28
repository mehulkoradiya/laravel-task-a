<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Upload;
use App\Jobs\AssembleUploadJob;

class ImageVariantGenerationTest extends TestCase
{
   use RefreshDatabase;

   public function test_variant_generation_creates_variants()
    {

        // make a sample jpeg in memory
        $im = imagecreatetruecolor(600, 400);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagejpeg($im, null, 90);
        $binary = ob_get_clean();
        imagedestroy($im);

        $uuid = \Illuminate\Support\Str::uuid();
        $filename = 'test-image.jpg';
        $path = "uploads/{$uuid}/{$filename}";

        // put file into fake disk
        Storage::disk('local')->put($path, $binary);

        // create Upload row
        $upload = Upload::create([
            'uuid'           => $uuid,
            'filename'       => $filename,
            'size'           => strlen($binary),
            'checksum'       => hash('sha256', $binary),
            'storage_disk'   => 'local',
            'status'         => 'completed',
            'total_chunks'   => 1,
            'received_chunks'=> 1,
        ]);

        // run job
        $job = new AssembleUploadJob($uuid);
        $job->handle();

        $this->assertDatabaseCount('images', 1);
        $this->assertDatabaseCount('image_variants', 3);
    }

}
