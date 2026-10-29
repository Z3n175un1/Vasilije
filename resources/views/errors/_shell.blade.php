<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} - DS TRANSPORTE S.R.L</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --ink: #1d1f3b;
            --muted: #5a5f7d;
            --primary: #2f2c79;
            --primary-dark: #1d1a50;
            --primary-light: #4b47b8;
            --primary-soft: #eceefe;
            --accent: #9ce0db;
            --accent-strong: #2bb3a8;
            --sun: #ffc857;
            --coral: #ff7a6b;
            --surface: #f6f7ff;
            --line: #dcdff0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { min-height: 100%; }

        body {
            font-family: 'Uncut Sans', 'Segoe UI', system-ui, sans-serif;
            color: var(--ink);
            background-color: var(--surface);
            background-image: radial-gradient(circle, rgba(47, 44, 121, 0.12) 1.2px, transparent 1.3px);
            background-size: 22px 22px;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .eh-card {
            position: relative;
            width: min(640px, 100%);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 28px 32px 30px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(29, 31, 59, 0.12);
            overflow: hidden;
        }

        .eh-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--primary), var(--accent-strong), var(--sun));
        }

        .eh-scene {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
        }

        .eh-scene svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .eh-code {
            margin-top: 8px;
            font-size: clamp(3.5rem, 10vw, 5.5rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, var(--primary), var(--accent-strong));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .eh-title {
            margin-top: 10px;
            font-size: 1.3rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--ink);
        }

        .eh-message {
            margin: 12px auto 0;
            max-width: 460px;
            color: var(--muted);
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.6;
        }

        .eh-actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .eh-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.85rem 1.6rem;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            box-shadow: 0 10px 22px rgba(47, 44, 121, 0.28);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .eh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(47, 44, 121, 0.35);
            color: #fff;
        }

        .eh-btn:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 3px;
        }

        .eh-brand {
            margin-top: 24px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
        }

        /* Animaciones compartidas por todas las escenas */
        .s-bob, .s-sway, .s-spin, .s-puff, .s-blink, .s-drift {
            transform-box: fill-box;
            transform-origin: center;
        }
        .s-bob   { animation: eh-bob 3s ease-in-out infinite; }
        .s-sway  { animation: eh-sway 3.2s ease-in-out infinite; }
        .s-spin  { animation: eh-spin 6s linear infinite; }
        .s-puff  { animation: eh-puff 2.6s ease-out infinite; }
        .s-blink { animation: eh-blink 1.4s steps(2, end) infinite; }
        .s-drift { animation: eh-drift 9s ease-in-out infinite alternate; }

        @keyframes eh-bob   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        @keyframes eh-sway  { 0%, 100% { transform: rotate(-3deg); } 50% { transform: rotate(3deg); } }
        @keyframes eh-spin  { to { transform: rotate(360deg); } }
        @keyframes eh-puff  { 0% { transform: translate(0, 0) scale(0.6); opacity: 0; } 20% { opacity: 0.9; } 100% { transform: translate(16px, -48px) scale(1.5); opacity: 0; } }
        @keyframes eh-blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.15; } }
        @keyframes eh-drift { 0% { transform: translateX(-14px); } 100% { transform: translateX(14px); } }

        @media (max-width: 520px) {
            .eh-card { padding: 22px 18px 26px; border-radius: 22px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .s-bob, .s-sway, .s-spin, .s-puff, .s-blink, .s-drift { animation: none !important; }
        }
    </style>
</head>
<body>
    <main>
        <section class="eh-card" aria-labelledby="eh-title">
            <div class="eh-scene" role="img" aria-label="{{ $sceneLabel }}">
                @include('errors.scenes.' . $scene)
            </div>

            <p class="eh-code" aria-hidden="true">{{ $code }}</p>
            <h1 id="eh-title" class="eh-title">{{ $title }}</h1>
            <p class="eh-message">{{ $message }}</p>

            <div class="eh-actions">
                <a href="{{ $action['url'] }}" class="eh-btn">
                    <i class="fas {{ $action['icon'] }}" aria-hidden="true"></i>
                    {{ $action['label'] }}
                </a>
            </div>

            <p class="eh-brand">DS TRANSPORTE S.R.L</p>
        </section>
    </main>
</body>
</html>
