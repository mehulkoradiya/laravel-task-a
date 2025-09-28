<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Jobs\AssembleUploadJob;

class UploadController extends Controller
{
     public function initiate(Request $r) 
     {
        $r->validate([
            'filename'=>'required|string',
            'size'=>'required|integer',
            'checksum'=>'nullable|string',
            'totalChunks'=>'required|integer',
        ]);

        $uuid = Str::uuid();

        $upload = Upload::create([
            'uuid'=>$uuid,
            'filename'=>$r->filename,
            'size'=>$r->size,
            'checksum'=>$r->checksum,
            'status'=>'uploading',
            'storage_disk'=>'local',
            'total_chunks'=>$r->totalChunks,
            'received_chunks'=>0,
            'metadata'=>$r->input('metadata', [])
        ]);

        // ensure tmp dir exists
        Storage::disk('local')->makeDirectory("tmp/uploads/{$uuid}/chunks");

        return response()->json(['upload_uuid'=>$uuid]);
    }

    public function uploadChunk(Request $r, $uuid) 
    {
        $r->validate(['chunkIndex'=>'required|integer','chunk'=>'required|file']);

        $upload = Upload::where('uuid',$uuid)->firstOrFail();

        $chunkIndex = (int)$r->chunkIndex;
        $chunkFile = $r->file('chunk');
        $path = "tmp/uploads/{$uuid}/chunks/{$chunkIndex}.part";

        Storage::disk('local')->putFileAs("tmp/uploads/{$uuid}/chunks", $chunkFile, "{$chunkIndex}.part.tmp");

        // atomically move 
        $tmpPath = "{$path}.tmp";
        if (!Storage::disk('local')->exists($tmpPath)) {
            return response()->json(['error' => 'Chunk file not found after upload'], 500);
        }
        Storage::disk('local')->move($tmpPath, $path);

        // update count in DB (optimistic)
        $upload->increment('received_chunks');

        return response()->json(['status'=>'ok','received_chunks'=>$upload->received_chunks]);
    }

    public function status($uuid) 
    {
        $upload = Upload::where('uuid',$uuid)->firstOrFail();
        $files = Storage::disk('local')->files("tmp/uploads/{$uuid}/chunks");

        $indices = array_map(function($f){
            return (int)basename($f, '.part');
        }, $files);

        return response()->json([
            'uuid'=>$uuid,
            'status'=>$upload->status,
            'received_chunks'=>$indices,
            'total_chunks'=>$upload->total_chunks
        ]);
    }

    public function complete(Request $r, $uuid) 
    {
        $r->validate(['checksum'=>'nullable|string']);
        $upload = Upload::where('uuid',$uuid)->firstOrFail();

        // Lock assembly using cache lock
        $lock = Cache::lock("upload:assemble:{$uuid}", 60);

        if (!$lock->get()) {
            return response()->json(['message'=>'Assembly in progress'], 423);
        }

        try {
            // check chunks
            $chunkFiles = Storage::disk('local')->files("tmp/uploads/{$uuid}/chunks");
            if (count($chunkFiles) < $upload->total_chunks) {
                $upload->update(['status'=>'failed']);
                return response()->json(['message'=>'Missing chunks'], 422);
            }

            $assembledPath = storage_path("app/tmp/uploads/{$uuid}/{$upload->filename}");

            // ensure assembled file directory exists
            @mkdir(dirname($assembledPath), 0777, true);

            // assemble (stream)
            $out = fopen($assembledPath, 'wb');

            for ($i=0;$i<$upload->total_chunks;$i++) {
                $chunkPath = Storage::disk('local')->path("tmp/uploads/{$uuid}/chunks/{$i}.part");
                $in = fopen($chunkPath,'rb');
                while (!feof($in)) {
                    fwrite($out, fread($in, 8192));
                }
                fclose($in);
            }

            fclose($out);

            // compute checksum if provided
            if ($r->checksum) {
                $sha = hash_file('sha256', $assembledPath);
                if ($sha !== $r->checksum) {
                    $upload->update(['status'=>'failed']);
                    return response()->json(['message'=>'Checksum mismatch'], 422);
                }
            }

            // move to permanent storage
            $permanentPath = "uploads/{$uuid}/{$upload->filename}";
            Storage::disk($upload->storage_disk)->putFileAs("uploads/{$uuid}", new \Illuminate\Http\File($assembledPath), $upload->filename);

            $upload->update(['status'=>'completed','checksum'=>$r->checksum,'size'=>filesize($assembledPath)]);
            
            // dispatch processing job
            AssembleUploadJob::dispatch($upload->uuid);

            return response()->json(['status'=>'completed','upload_uuid'=>$upload->uuid]);
        } finally {
            $lock->release();
        }
    }
}
