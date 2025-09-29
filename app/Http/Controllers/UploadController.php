<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Jobs\AssembleUploadJob;
use RahulHaque\Filepond\Rules\Filepond as FilepondRule;
use Illuminate\Validation\Rule;
use RahulHaque\Filepond\Facades\Filepond;

class UploadController extends Controller
{
    
    public function store(Request $request)
    {
        $request->validate([
            'images.*' => Rule::filepond([
                'required',
                'image',
                'max:102400'
            ])
        ]);

        // Dispatch job for async processing
        AssembleUploadJob::dispatch($request->images);

        // Show success message in blade view
        return view('uploads', [
            'success' => 'Images saved successfully.',
        ]);
    }

    
}
