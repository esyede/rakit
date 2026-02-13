<!-- Header start -->

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style>
        html.dark-preload body {
            visibility: hidden !important;
        }
    </style>
    <script>
        // Tambahkan class dark-preload sebelum CSS utama
        document.documentElement.classList.add('dark-preload');
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' || (savedTheme === null && window.matchMedia && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <link rel="stylesheet" href="{{ asset('packages/docs/css/docs.css?v=' . RAKIT_VERSION) }}">
    <link rel="stylesheet" href="{{ asset('packages/docs/css/dark.css?v=' . RAKIT_VERSION) }}">
    <title>Rakit :: Documentation ~ {{ $title }}</title>

    <style>
        #searchModalWrapper {
            position: fixed;
            top: 12vh;
            left: 50%;
            transform: translateX(-50%);
            width: 90vw;
            max-width: 600px;
            z-index: 1001;
            background: transparent;
        }

        #searchInputModalCustom {
            width: 100%;
            height: 3.2rem;
            font-size: 1.15rem;
            border-radius: 0;
            border: 1.5px solid hsl(var(--border));
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
            box-sizing: border-box;
            padding: 0 1.2em;
            outline: none;
            background: hsl(var(--input));
            color: hsl(var(--foreground));
            transition: border 0.2s, box-shadow 0.2s, background 0.2s, color 0.2s;
        }

        #searchInputModalCustom:focus {
            border: 1.5px solid hsl(var(--primary));
            box-shadow: 0 6px 24px hsla(var(--primary), 0.10);
            background: hsl(var(--background));
        }

        #searchResultsCustom {
            width: 100%;
            background: hsl(var(--card));
            border-radius: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.10);
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            max-height: 60vh;
            overflow-y: auto;
            border: 1.5px solid hsl(var(--border));
            border-top: none;
            color: hsl(var(--card-foreground));
        }

        #searchResultsCustom a {
            display: block;
            width: 100%;
            padding: 1em 1.2em 0.7em 1.2em;
            color: hsl(var(--foreground));
            text-decoration: none;
            border-bottom: 1px solid hsl(var(--muted));
            box-sizing: border-box;
            background: transparent;
            transition: background 0.18s, color 0.18s;
            font-size: 1.05em;
            cursor: pointer;
        }

        #searchResultsCustom a:last-child {
            border-bottom: none;
        }

        #searchResultsCustom a:hover,
        #searchResultsCustom a:focus {
            background: hsl(var(--muted));
            color: hsl(var(--primary));
        }

        #searchResultsCustom .snippet {
            color: hsl(var(--muted-foreground));
            font-size: 0.98em;
            margin-top: 0.25em;
            word-break: break-word;
        }

        #searchResultsCustom mark {
            background: hsl(var(--accent));
            color: hsl(var(--accent-foreground));
            padding: 0 2px;
            border-radius: 2px;
        }

        #searchResultsCustom::-webkit-scrollbar {
            width: 10px;
        }

        #searchResultsCustom::-webkit-scrollbar-thumb {
            background: hsl(var(--border));
            border-radius: 6px;
            border: 2px solid hsl(var(--card));
        }

        #searchResultsCustom::-webkit-scrollbar-track {
            background: hsl(var(--card));
            border-radius: 0;
        }

        #searchResultsCustom {
            scrollbar-width: thin;
            scrollbar-color: hsl(var(--border)) hsl(var(--card));
        }

        @media (max-width: 600px) {
            #searchModalWrapper {
                max-width: 98vw;
                width: 98vw;
            }

            #searchInputModalCustom,
            #searchResultsCustom {
                font-size: 1em;
                padding-left: 0.7em;
                padding-right: 0.7em;
            }
        }
    </style>



    <meta property="og:type" content="website">
    <meta property="og:title" content="Rakit :: Documentation">
    <meta property="og:description" content="Rakit framework documentation">
    <meta property="og:site_name" content="Rakit :: Documentation">
</head>
<!-- Header end -->
