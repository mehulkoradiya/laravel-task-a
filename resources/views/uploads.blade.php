@extends('layout')

@section('content')
<div class="card p-4">
    <h4>Drag & Drop Image Upload</h4>
    @if(isset($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif

    <form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!--  For multiple file uploads  -->
        <input type="file" id="filepond" name="images[]" multiple required/>
        <div id="filepond-error" class="text-red-600 text-sm mt-2"></div>

        <button id="submit-btn" class="btn btn-primary" type="submit">Submit</button>
    </form>
</div>
@endsection

@section('scripts')
<link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
<script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
<script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
<script src="https://unpkg.com/filepond/dist/filepond.js"></script>
<script>
    const errorDiv = document.getElementById('filepond-error');
    const submitBtn = document.getElementById('submit-btn');
    FilePond.registerPlugin(FilePondPluginFileValidateSize, FilePondPluginFileValidateType);
    
    document.addEventListener('FilePond:loaded', () => {
            const inputElement = document.querySelector('input[id="filepond"]');
            
            // Create a FilePond instance
            const pond = FilePond.create(inputElement, {
                chunkUploads: true,
                chunkSize: "1MB",
                chunkRetryDelays: [2000, 5000, 10000],
                server: {
                    url: '/filepond',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                },
                acceptedFileTypes: ['image/*'],
                allowFileTypeValidation: true,
                maxFileSize: '100MB',
                allowFileSizeValidation: true,
                labelMaxFileSizeExceeded: 'File is too large (max 100MB)',
                onerror: (error) => {
                    errorDiv.textContent = (error && (error.body || error.main)) || 'An error occurred during upload.';
                },
                onprocessfile: (error, file) => {
                    if (error) {
                        console.log(error);
                        errorDiv.textContent = (error && (error.body || error.main)) || 'An error occurred during upload.';
                    } else {
                        errorDiv.textContent = '';
                    }
                }
            });

            // Disable submit button while uploading
            pond.on('addfile', () => {
                if (submitBtn) submitBtn.disabled = true;
            });
            pond.on('processfile', () => {
                if (pond.getFiles().every(file => file.status === 5)) { // 5 = FilePond.FileStatus.PROCESSING_COMPLETE
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
            pond.on('processfileprogress', () => {
                if (submitBtn) submitBtn.disabled = true;
            });
            pond.on('removefile', () => {
                if (pond.getFiles().length === 0 || pond.getFiles().every(file => file.status === 5)) {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
    })
      
</script>
@endsection
