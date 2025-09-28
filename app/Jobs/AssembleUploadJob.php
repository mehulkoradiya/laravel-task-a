<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\Image;
use App\Models\ImageVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Facades\Image as InterventionImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;

class AssembleUploadJob implements ShouldQueue
{
    use Queueable, SerializesModels, Dispatchable;

    protected $uuid;

    /**
     * Create a new job instance.
     */
    public function __construct($uuid) {
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $upload = Upload::where('uuid', $this->uuid)->first();
        if (!$upload || $upload->status !== 'completed') {
            return;
        }

        $disk = $upload->storage_disk;
        $pathInDisk = "uploads/{$this->uuid}/{$upload->filename}";

        if (!Storage::disk($disk)->exists($pathInDisk)) {
            $upload->update(['status' => 'failed']);
            return;
        }

        // Load file contents directly (works with fake and real storage)
        $content = Storage::disk($disk)->get($pathInDisk);

        // Make Intervention image directly from binary
        $img = InterventionImage::make($content);

        // Create main Image record
        $image = Image::create([
            'upload_id'  => $upload->id,
            'path'       => $pathInDisk,
            'checksum'   => $upload->checksum,
            'width'      => $img->width(),
            'height'     => $img->height(),
            'size_bytes' => $upload->size,
            'metadata'   => []
        ]);

        // Generate variants
        foreach ([256, 512, 1024] as $v) {
            $variantPath = "uploads/{$this->uuid}/variants/{$v}_{$upload->filename}";

            $resized = InterventionImage::make($content)->resize($v, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('jpg', 85);

            Storage::disk($disk)->put($variantPath, (string) $resized);

            $variantImg = InterventionImage::make($resized);
            ImageVariant::create([
                'image_id'   => $image->id,
                'variant'    => (string)$v,
                'path'       => $variantPath,
                'width'      => $variantImg->width(),
                'height'     => $variantImg->height(),
                'size_bytes' => strlen((string)$resized),
            ]);
        }
    }
}
