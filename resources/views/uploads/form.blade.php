<form method="POST" action="{{ route('uploads.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="file" name="images[]" id="filepond" multiple>
    <button id="submitBtn" type="submit" disabled>Submit</button>
</form>

<script src="https://unpkg.com/filepond/dist/filepond.js"></script>
<link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet" />
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submitBtn');
    const pond = FilePond.create(document.getElementById('filepond'));

    pond.on('processfile', () => {
        if (pond.getFiles().every(f => f.status === 5)) {
            submitBtn.disabled = false;
        }
    });

    pond.on('addfile', () => {
        submitBtn.disabled = true;
    });

    pond.on('removefile', () => {
        submitBtn.disabled = true;
        if (pond.getFiles().length === 0) {
            submitBtn.disabled = true;
        }
    });
});
</script>
