@extends('layout')

@section('content')
<div class="card p-4">
    <h4>Drag & Drop Image Upload</h4>

    <div id="dropzone" class="dropzone">Drop image here or click to select</div>
    <input type="file" id="fileInput" class="d-none" accept="image/*">

    <div id="progress-section" class="mt-3" style="display:none;">
        <progress id="progressBar" value="0" max="100"></progress>
        <p id="progressText"></p>
    </div>
</div>
@endsection

@section('scripts')
<script>
const chunkSize = 256 * 1024; // 256 KB
let file, uploadId, checksum;

$('#dropzone').on('click', ()=>$('#fileInput').click());
$('#fileInput').on('change', e => startUpload(e.target.files[0]));
$('#dropzone').on('dragover', e=>{ e.preventDefault(); $('#dropzone').addClass('dragover') });
$('#dropzone').on('dragleave', e=>$('#dropzone').removeClass('dragover'));
$('#dropzone').on('drop', e=>{
    e.preventDefault();
    $('#dropzone').removeClass('dragover');
    startUpload(e.originalEvent.dataTransfer.files[0]);
});

async function sha256(file){
    const buf = await file.arrayBuffer();
    const hash = await crypto.subtle.digest('SHA-256', buf);
    return Array.from(new Uint8Array(hash)).map(b=>b.toString(16).padStart(2,'0')).join('');
}

async function startUpload(f){
    file = f;
    checksum = await sha256(file);
    const totalChunks = Math.ceil(file.size / chunkSize);

    $.post({
        url:'/api/uploads/initiate',
        contentType:'application/json',
        data: JSON.stringify({
            filename: file.name,
            size: file.size,
            checksum,
            totalChunks
        }),
        success: async function(res){
            uploadId = res.upload_uuid;
            $('#progress-section').show();
            await uploadChunks(totalChunks);
            await completeUpload();
        }
    });
}

async function uploadChunks(totalChunks){
    for(let i=0;i<totalChunks;i++){
        const start = i*chunkSize;
        const end = Math.min(file.size, start+chunkSize);
        const blob = file.slice(start,end);

        let fd = new FormData();
        fd.append('chunkIndex', i);
        fd.append('chunk', blob);

        await $.ajax({
            url:`/api/uploads/${uploadId}/chunk`,
            method:'POST',
            data:fd,
            processData:false,
            contentType:false
        });

        const percent = Math.round(((i+1)/totalChunks)*100);
        $('#progressBar').val(percent);
        $('#progressText').text(`Uploaded ${i+1}/${totalChunks} chunks (${percent}%)`);
    }
}

async function completeUpload(){
    await $.post({
        url:`/api/uploads/${uploadId}/complete`,
        contentType:'application/json',
        data: JSON.stringify({checksum})
    });
    $('#progressText').append('<br><span class="text-success">Upload complete!</span>');
}
</script>
@endsection
