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
use RahulHaque\Filepond\Facades\Filepond;
use Illuminate\Support\Str;

class AssembleUploadJob implements ShouldQueue
{
    use Queueable, SerializesModels, Dispatchable;

    protected array  $images;
    protected string $disk;

    /**
     * Create a new job instance.
     */
    public function __construct(array $images, string $disk = 'public') {
        $this->images = $images;
        $this->disk = $disk;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fileInfo = Filepond::field($this->images)->moveTo('uploads/originals');
        foreach ($fileInfo as $info) {
            // Generate a unique name for the image
            $uniqueName = Str::uuid() . '.' . ($info['extension'] ?? pathinfo($info['basename'], PATHINFO_EXTENSION));
            $originalPath = 'uploads/originals/' . $uniqueName;

            // Move/rename the file to the unique path
            Storage::disk($this->disk)->move($info['location'], $originalPath);

            if (!Storage::disk($this->disk)->exists($originalPath)) {
                continue;
            }

            $content = Storage::disk($this->disk)->get($originalPath);
            try {
                $img = InterventionImage::make($content);
            } catch (\Exception $e) {
                // Skip invalid images
                continue;
            }

            // Save Image record
            $image = Image::create([
                'uuid'       => Str::uuid(),
                'path'       => $originalPath,
                'checksum'   => hash('sha256', $content),
                'width'      => $img->width(),
                'height'     => $img->height(),
                'size_bytes' => strlen($content),
                'metadata'   => [],
            ]);

            // Generate variants
            foreach ([256, 512, 1024] as $v) {
                $variantPath = "uploads/variants/{$v}_{$uniqueName}";

                $resized = $img->resize($v, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->encode('jpg', 85);

                Storage::disk($this->disk)->put($variantPath, (string) $resized);

                $variantImg = InterventionImage::make($resized);
                ImageVariant::create([
                    'image_id'   => $image->id,
                    'variant'    => (string) $v,
                    'path'       => $variantPath,
                    'width'      => $variantImg->width(),
                    'height'     => $variantImg->height(),
                    'size_bytes' => strlen((string) $resized),
                ]);
            }
        }
    }
}
