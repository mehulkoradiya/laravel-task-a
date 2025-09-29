@extends('layout')

@section('content')
<div class="card p-4">
    <h4>CSV Import Products</h4>

    <form id="csv-form" enctype="multipart/form-data">
        <input type="file" name="file" accept=".csv" class="form-control mb-3" required>
    <button type="submit" class="btn btn-primary" id="uploadBtn">Upload CSV</button>
    </form>

    <div id="import-status" class="mt-4"></div>
</div>
@endsection

@section('scripts')
<script>
$('#uploadBtn').prop('disabled', false);
$('#csv-form').on('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);
    $('#uploadBtn').prop('disabled', true);
    $.ajax({
        url: '/api/products/import',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res){
            $('#import-status').html(`<p>Import started. ID: ${res.import_id}</p>`);
            pollStatus(res.import_id);
        },
        error: function(xhr){
            alert(xhr.responseText);
            $('#uploadBtn').prop('disabled', false);
        }
    });
});

function pollStatus(id){
    $.getJSON(`/api/products/import/${id}/status`, function(data){
        $('#import-status').html(`<pre>${JSON.stringify(data, null, 2)}</pre>`);
        if(data.status === 'running' || data.status === 'queued'){
            setTimeout(()=>pollStatus(id), 2000);
        } else {
            // Enable button when import is finished
            $('#uploadBtn').prop('disabled', false);
        }
    });
}
</script>
@endsection
