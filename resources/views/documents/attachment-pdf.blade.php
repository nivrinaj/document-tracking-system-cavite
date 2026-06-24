<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; }
        .page { width: 100%; height: 100%; text-align: center; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        img { max-width: 100%; max-height: 100%; }
    </style>
</head>
<body>
    @foreach($pages as $src)
        <div class="page"><img src="{{ $src }}"></div>
    @endforeach
</body>
</html>
