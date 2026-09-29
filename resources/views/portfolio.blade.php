<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('demo.name') }} — {{ config('demo.portfolio.page_title') }}</title>
    <style>
        :root { color-scheme: dark; --bg: #020617; --surface: #0f172a; --border: #1e293b; --text: #f1f5f9; --muted: #94a3b8; --accent: #22d3ee; }
        body { font-family: system-ui, sans-serif; margin: 0; background: var(--bg); color: var(--text); line-height: 1.55; }
        .wrap { max-width: 48rem; margin: 0 auto; padding: 1.5rem clamp(1rem, 4vw, 1.5rem) 3rem; }
        h1 { font-size: clamp(1.125rem, 2.5vw, 1.35rem); margin: 0 0 0.35rem; line-height: 1.3; scroll-margin-top: 0.5rem; }
        h2 { font-size: 1.05rem; margin: 2rem 0 0.75rem; color: #e2e8f0; }
        h3 { font-size: 0.9375rem; margin: 0 0 0.35rem; }
        p, li { font-size: 0.9375rem; }
        .meta { color: var(--muted); font-size: 0.875rem; margin: 0 0 1rem; }
        a { color: var(--accent); }
        a:hover { text-decoration: underline; }
        .stack-nav { margin-bottom: 1.5rem; }
        .lead { font-size: 1rem; color: #e2e8f0; margin: 0 0 1rem; }
        .card { border: 1px solid var(--border); border-radius: 0.75rem; background: var(--surface); padding: 1rem 1.15rem; margin-bottom: 0.75rem; }
        .card p { margin: 0.35rem 0 0; color: var(--muted); font-size: 0.875rem; }
        .card .tenure { color: var(--accent); font-size: 0.8125rem; margin: 0 0 0.25rem; }
        .card .relevance { margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px solid var(--border); color: #cbd5e1; }
        code, .mono { font-family: ui-monospace, Menlo, Monaco, Consolas, monospace; font-size: 0.8125rem; }
        ul.plain, ol.plain { margin: 0; padding-left: 1.15rem; }
        ul.plain li, ol.plain li { margin-bottom: 0.45rem; }
        .disclaimer { border-left: 3px solid #475569; padding-left: 0.75rem; color: var(--muted); font-size: 0.875rem; margin: 1rem 0; }
    </style>
    <link rel="stylesheet" href="/legacy/back-to-top.css?v={{ config('demo.legacy_asset_version', '3') }}" />
</head>
<body data-page-heading="page-heading">
@php
    $p = config('demo.portfolio');
@endphp
<div class="wrap">
    <h1 id="page-heading">{{ $p['page_title'] }}</h1>
    <p class="meta stack-nav">
        <a href="/">Live demo</a>
        · <a href="/legacy/admin.html">Legacy screens</a>
        · <span aria-current="page">HR review guide</span>
    </p>

    <p class="meta"><strong>{{ $p['author'] }}</strong> · <a href="{{ $p['author_site'] }}">jorap.com</a></p>

    <p class="lead">{{ $p['elevator'] }}</p>

    <p class="disclaimer">
        <strong>Sample product only.</strong> {{ config('demo.name') }} is not a real telecommunications company. Customer and partner names are fictional placeholders for demo data.
    </p>

    <h2>What this live sample is</h2>
    <p>{{ $p['summary'] }}</p>

    <h2>Five-minute walkthrough (no coding)</h2>
    <ol class="plain">
        @foreach ($p['review_steps'] as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>

    <h2>Links to try in the browser</h2>
    @if (! empty($p['live_url']))
        <p class="meta">Deployed site: <a href="{{ rtrim($p['live_url'], '/') }}">{{ $p['live_url'] }}</a></p>
    @endif
    @foreach ($p['surfaces'] as $surface)
        <div class="card">
            <h3><a href="{{ $surface['href'] }}">{{ $surface['label'] }}</a></h3>
            <p>{{ $surface['note'] }}</p>
        </div>
    @endforeach

    <h2>Employment background (code not shareable)</h2>
    <p class="meta">Written summaries for HR files. Past employer and client code stay private where NDAs apply. No client URLs on this page.</p>
    @foreach ($p['commercial_work'] as $job)
        <div class="card">
            <h3>{{ $job['role'] }}</h3>
            <p class="tenure">{{ $job['tenure'] }}</p>
            <p><strong>Technologies:</strong> {{ $job['stack'] }}</p>
            <p><strong>Scope:</strong> {{ $job['contribution'] }}</p>
            <p class="relevance"><strong>Why it matters for hiring:</strong> {{ $job['relevance'] }}</p>
        </div>
    @endforeach

    <p class="meta" style="margin-top: 2rem;"><a href="/">Return to live demo</a></p>
</div>
<script src="/legacy/legacy-scroll.js?v={{ config('demo.legacy_asset_version', '3') }}"></script>
<script>
(function () {
    if (window.LegacyScroll) {
        LegacyScroll.bindSectionScroll(document.querySelector('.wrap'), 'page-heading');
    }
})();
</script>
</body>
</html>
