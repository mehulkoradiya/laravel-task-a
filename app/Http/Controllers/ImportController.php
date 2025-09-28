<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ImportProductsJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function uploadCsv(Request $request) 
    {
        $request->validate(['file'=>'required|file|mimes:csv,txt|max:102400']);

        $file = $request->file('file');
        $importId = Str::uuid();

        // make sure the folder exists
        Storage::disk('local')->makeDirectory('imports');

        // store file
        $path = "imports/{$importId}.csv";
        Storage::disk('local')->putFileAs('imports', $file, "{$importId}.csv");

        // get absolute path
        $absolutePath = Storage::disk('local')->path($path);

        // cache import status
        cache(["product_import:{$importId}" => [
            'status'=>'queued','total'=>0,'imported'=>0,'updated'=>0,'invalid'=>0,'duplicates'=>0,'errors'=>[]
        ]], now()->addHours(2));

        // dispatch job
        ImportProductsJob::dispatch($importId, $absolutePath);

        return response()->json(['import_id'=>$importId], 202);
    }

    public function status($id) 
    {
        $data = cache("product_import:{$id}");
        if (!$data) return response()->json(['message'=>'Not found'], 404);
        return response()->json($data);
    }
}
