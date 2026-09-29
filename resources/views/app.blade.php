<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('demo.name') }} — {{ config('demo.desk_title') }}</title>
    <link rel="stylesheet" href="/legacy/back-to-top.css?v={{ config('demo.legacy_asset_version', '9') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page-heading="page-heading">
    <div id="app"></div>
    <script src="/legacy/legacy-scroll.js?v={{ config('demo.legacy_asset_version', '9') }}" defer></script>
</body>
</html>
