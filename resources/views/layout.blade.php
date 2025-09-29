<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task A - Bulk Import & Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dropzone {
            border: 2px dashed #aaa;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            border-radius: 8px;
        }
        .dropzone.dragover {
            background: #f1f5f9;
        }
        progress {
            width: 100%;
            height: 20px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <nav class="mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-link">Dashboard</a>
        <a href="{{ route('imports') }}" class="btn btn-link">CSV Import</a>
        <a href="{{ route('uploads') }}" class="btn btn-link">Image Upload</a>
    </nav>

    @yield('content')
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@yield('scripts')
</body>
</html>
