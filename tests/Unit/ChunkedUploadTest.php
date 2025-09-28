<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Upload;

class ChunkedUploadTest extends TestCase 
{
    use RefreshDatabase;

    public function test_chunk_upload_and_complete_assembles()
    {
        // generate a valid tiny JPEG
        $im = imagecreatetruecolor(10, 10);
        $bg = imagecolorallocate($im, 255, 0, 0);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagejpeg($im, null, 80);
        $binary = ob_get_clean();
        imagedestroy($im);

        // split into 2 chunks
        $chunkSize = ceil(strlen($binary) / 2);
        $chunk0 = substr($binary, 0, $chunkSize);
        $chunk1 = substr($binary, $chunkSize);

        // initiate
        $res = $this->postJson('/api/uploads/initiate', [
            'filename' => 'test.jpg',
            'size' => strlen($binary),
            'checksum' => hash('sha256', $binary),
            'totalChunks' => 2
        ]);

        $res->assertStatus(200);
        $uuid = $res->json('upload_uuid');

        // upload chunks
        $this->postJson("/api/uploads/{$uuid}/chunk", [
            'chunkIndex' => 0,
            'chunk' => \Illuminate\Http\UploadedFile::fake()->createWithContent('c0.part', $chunk0)
        ])->assertStatus(200);

        $this->postJson("/api/uploads/{$uuid}/chunk", [
            'chunkIndex' => 1,
            'chunk' => \Illuminate\Http\UploadedFile::fake()->createWithContent('c1.part', $chunk1)
        ])->assertStatus(200);

        // complete
        $this->postJson("/api/uploads/{$uuid}/complete", [])
            ->assertStatus(200);

        $this->assertDatabaseHas('uploads', ['uuid' => $uuid, 'status' => 'completed']);
    }

}
