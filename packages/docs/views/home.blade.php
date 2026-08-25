@layout('docs::layout')

@section('sidebar')
    <aside class="docs__side" id="sidebar-toc">
        {!! $sidebar !!}
    </aside>
    <div class="docs__scrim" id="sidebarScrim"></div>
    <button type="button" class="docs__toggle" id="sidebarToggle" aria-controls="sidebar-toc" aria-expanded="false"
        aria-label="Table of contents">
        <svg class="docs__toggle-menu" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M1 3h14M1 8h14M1 13h14" />
        </svg>
        <svg class="docs__toggle-close" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M3 3l10 10M13 3L3 13" />
        </svg>
    </button>
@endsection

@section('content')
    @if ($outline)
        <aside class="docs__rail" aria-label="On this page">
            <p class="docs__rail-title">On this page</p>
            {!! $outline !!}
        </aside>
    @endif

    <div class="docs__main">
        <div class="docs__bar">
            <nav class="crumbs" aria-label="Breadcrumb">
                @foreach ($breadcrumbs as $index => $crumb)
                    @if ($index > 0)
                        <span class="crumbs__sep" aria-hidden="true">/</span>
                    @endif

                    @if ($index === count($breadcrumbs) - 1)
                        <span aria-current="page">{{ $crumb['name'] }}</span>
                    @elseif ($crumb['url'])
                        <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                    @else
                        <span>{{ $crumb['name'] }}</span>
                    @endif
                @endforeach
            </nav>
            <a class="btn btn--ghost btn--sm"
                href="https://github.com/esyede/rakit/edit/main/packages/docs/data/{{ $file }}.md" target="_blank">
                <svg class="btn__icon" viewBox="0 0 64 64" aria-hidden="true">
                    <path stroke-width="0" fill="currentColor"
                        d="M32 0 C14 0 0 14 0 32 0 53 19 62 22 62 24 62 24 61 24 60 L24 55 C17 57 14 53 13 50 13 50 13 49 11 47 10 46 6 44 10 44 13 44 15 48 15 48 18 52 22 51 24 50 24 48 26 46 26 46 18 45 12 42 12 31 12 27 13 24 15 22 15 22 13 18 15 13 15 13 20 13 24 17 27 15 37 15 40 17 44 13 49 13 49 13 51 20 49 22 49 22 51 24 52 27 52 31 52 42 45 45 38 46 39 47 40 49 40 52 L40 60 C40 61 40 62 42 62 45 62 64 53 64 32 64 14 50 0 32 0 Z" />
                </svg>
                Edit this page
            </a>
        </div>

        <div class="prose">
            {!! $content !!}
        </div>

        @if ($neighbours['previous'] || $neighbours['next'])
            <nav class="docs__nav" aria-label="Page navigation">
                @if ($neighbours['previous'])
                    <a class="docs__nav-link" href="{{ $neighbours['previous']['url'] }}" rel="prev">
                        <span class="docs__nav-dir">&larr; Previous</span>
                        <span class="docs__nav-name">
                            @if ($neighbours['previous']['section'])
                                <span class="docs__nav-section">{{ $neighbours['previous']['section'] }} &middot;</span>
                            @endif
                            {{ $neighbours['previous']['name'] }}
                        </span>
                    </a>
                @endif

                @if ($neighbours['next'])
                    <a class="docs__nav-link docs__nav-link--next" href="{{ $neighbours['next']['url'] }}" rel="next">
                        <span class="docs__nav-dir">Next &rarr;</span>
                        <span class="docs__nav-name">
                            @if ($neighbours['next']['section'])
                                <span class="docs__nav-section">{{ $neighbours['next']['section'] }} &middot;</span>
                            @endif
                            {{ $neighbours['next']['name'] }}
                        </span>
                    </a>
                @endif
            </nav>
        @endif

        <p class="docs__updated">
            Rakit {{ RAKIT_VERSION }} &middot; last updated {{ date('j M Y', $modified) }}
        </p>
    </div>

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        'headline' => 'Rakit :: Documentation ~ ' . $title,
        'name' => $title,
        'description' => $description,
        'url' => $canonical,
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
        'dateModified' => date(DATE_W3C, $modified),
        'inLanguage' => 'en',
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'Rakit :: Documentation',
            'url' => rtrim(url('/'), '/'),
        ],
        'author' => [
            '@type' => 'Organization',
            'name' => 'Rakit Contributors',
            'url' => 'https://github.com/esyede/rakit/contributors',
        ],
        'about' => [
            '@type' => 'SoftwareApplication',
            'name' => 'Rakit',
            'applicationCategory' => 'DeveloperApplication',
            'softwareVersion' => RAKIT_VERSION,
            'operatingSystem' => 'PHP 5.4 to 8.x',
            'url' => 'https://github.com/esyede/rakit',
        ],
        'license' => 'https://github.com/esyede/rakit/blob/main/LICENSE',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(function ($crumb, $position) {
            $item = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $crumb['name']];

            if ($crumb['url']) {
                $item['item'] = $crumb['url'];
            }

            return $item;
        }, $breadcrumbs, array_keys($breadcrumbs)),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
