<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · Platform Security</title>
    <style>
        :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7fb; color: #172033; }
        main { width: min(100% - 2rem, 42rem); margin: 4rem auto; }
        .card { background: white; border: 1px solid #dce2ec; border-radius: 1rem; padding: 2rem; box-shadow: 0 1rem 3rem rgb(23 32 51 / 8%); }
        h1 { margin-top: 0; font-size: 1.65rem; }
        h2 { margin-top: 2rem; font-size: 1.1rem; }
        label { display: block; margin-top: 1rem; font-weight: 650; }
        input { width: 100%; margin-top: .4rem; padding: .75rem; border: 1px solid #aeb8c8; border-radius: .55rem; font: inherit; }
        button, .button { display: inline-block; margin-top: 1.25rem; padding: .72rem 1rem; border: 0; border-radius: .55rem; background: #1d4ed8; color: white; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        button.danger { background: #b42318; }
        .muted { color: #5d6879; }
        .error { color: #b42318; }
        .status { padding: .75rem; border-radius: .5rem; background: #ecfdf3; color: #067647; }
        .qr { display: grid; place-items: center; margin: 1.5rem 0; }
        .qr svg { width: min(100%, 16rem); height: auto; }
        code { overflow-wrap: anywhere; }
        .codes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; padding: 0; list-style: none; }
        .codes code { display: block; padding: .65rem; border: 1px solid #dce2ec; border-radius: .45rem; text-align: center; }
        .session { padding: 1rem 0; border-top: 1px solid #e6eaf0; }
        .session p { margin: .25rem 0; }
        @media (max-width: 32rem) { main { margin: 1rem auto; } .card { padding: 1.25rem; } .codes { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <section class="card">
        @if (session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </section>
</main>
</body>
</html>
