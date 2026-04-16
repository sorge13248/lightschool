<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="refresh" content="120">
    <title>Manutenzione — {{ config('app.name', 'LightSchool') }}</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow: hidden;
            background: #0b1120;
        }

        /* ── Animated gradient background ───────────────────────────────────── */
        .bg {
            position: fixed;
            inset: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg at 40% 50%,
                    #0b1120 0deg,
                    #1a2a6c 60deg,
                    #1e6bc9 120deg,
                    #0b1120 180deg,
                    #0b1120 240deg,
                    #1a2a6c 300deg,
                    #0b1120 360deg);
            animation: spin 18s linear infinite;
            filter: blur(60px);
            opacity: 0.85;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: drift 12s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 520px;
            height: 520px;
            background: #1e6bc9;
            top: -120px;
            left: -80px;
            animation-duration: 14s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #6b3fa0;
            bottom: -100px;
            right: -60px;
            animation-duration: 11s;
            animation-delay: -4s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: #0ea5e9;
            top: 40%;
            left: 55%;
            animation-duration: 16s;
            animation-delay: -7s;
        }

        @keyframes drift {
            from {
                transform: translate(0, 0) scale(1);
            }

            to {
                transform: translate(40px, 30px) scale(1.08);
            }
        }

        /* ── Liquid glass card ───────────────────────────────────────────────── */
        .card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
            margin: 20px;
            padding: 52px 44px 44px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(32px) saturate(180%) brightness(1.05);
            -webkit-backdrop-filter: blur(32px) saturate(180%) brightness(1.05);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow:
                0 2px 0 rgba(255, 255, 255, 0.12) inset,
                0 -1px 0 rgba(0, 0, 0, 0.25) inset,
                0 32px 80px rgba(0, 0, 0, 0.55),
                0 4px 24px rgba(30, 107, 201, 0.18);
            text-align: center;
            animation: appear 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Subtle top gloss line */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
            border-radius: 50%;
        }

        /* ── Icon ────────────────────────────────────────────────────────────── */
        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.15) inset, 0 8px 24px rgba(0, 0, 0, 0.3);
            margin-top: -10rem;
            margin-bottom: 2rem;
        }

        .icon-wrap img {
            width: 128px;
            height: 128px;
            box-shadow: 0 5px 10px #1e6bc9;
            border-radius: 1rem;
            z-index: 1;
        }

        .icon-wrap svg {
            width: 38px;
            height: 38px;
            color: rgba(255, 255, 255, 0.92);
            animation: gear-spin 6s linear infinite;
            transform-origin: center;
        }

        @keyframes gear-spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Text ────────────────────────────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72em;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 16px;
        }

        h1 {
            font-size: 1.85em;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 14px;
            letter-spacing: -0.02em;
        }

        p {
            font-size: 0.95em;
            color: rgba(255, 255, 255, 0.55);
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* ── Divider ─────────────────────────────────────────────────────────── */
        .divider {
            margin: 32px 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12), transparent);
        }

        /* ── Progress dots ───────────────────────────────────────────────────── */
        .dots {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            animation: blink 1.6s ease-in-out infinite;
        }

        .dot:nth-child(2) {
            animation-delay: 0.28s;
        }

        .dot:nth-child(3) {
            animation-delay: 0.56s;
        }

        @keyframes blink {

            0%,
            80%,
            100% {
                opacity: 0.25;
                transform: scale(1);
            }

            40% {
                opacity: 1;
                transform: scale(1.3);
                background: #60a5fa;
            }
        }

        /* ── Footer ──────────────────────────────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 24px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.78em;
            color: rgba(255, 255, 255, 0.25);
            z-index: 10;
        }

        a {
            color: rgba(255, 255, 255, 0.25);
        }
    </style>
</head>

<body>

    <div class="bg"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="card">
        <div class="icon-wrap">
            <img src="{{ asset('img/logo.png') }}" alt="" />
        </div>

        <main>
            <h1>Stiamo lavorando per te</h1>

            <p>
                {{ config('app.name', 'LightSchool') }} è momentaneamente offline
                per lavori di manutenzione.<br>
                Torneremo online al più presto.
            </p>
        </main>

        <div class="divider"></div>

        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <div class="footer">
        <a href="https://github.com/sorge13248/lightschool">GitHub</a>
    </div>

</body>

</html>
