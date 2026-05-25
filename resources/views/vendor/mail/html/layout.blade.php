<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #eef7f2;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1f2937;
        }
        .email-wrapper {
            max-width: 560px;
            margin: 40px auto;
            padding: 0 16px 40px;
        }
        .email-header {
            text-align: center;
            padding: 32px 0 24px;
        }
        .email-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .email-logo-icon {
            width: 40px;
            height: 40px;
            background-color: #2d7a56;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .email-logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #1A4731;
        }
        .email-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #d4e9dc;
            padding: 40px;
            box-shadow: 0 2px 12px rgba(45,122,86,0.07);
        }
        .email-footer {
            text-align: center;
            padding: 24px 0 0;
            font-size: 12px;
            color: #9ca3af;
        }
        .email-footer a {
            color: #2d7a56;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">

        {{-- Header / Logo --}}
        <div class="email-header">
            <span class="email-logo">
                <span class="email-logo-icon">
                    {{-- house icon via inline SVG --}}
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 9.5L12 3L21 9.5V20C21 20.55 20.55 21 20 21H15V15H9V21H4C3.45 21 3 20.55 3 20V9.5Z"
                              fill="white"/>
                    </svg>
                </span>
                <span class="email-logo-text">Kos Firabo</span>
            </span>
        </div>

        {{-- Konten utama --}}
        <div class="email-card">
            {{ Illuminate\Mail\Markdown::parse($slot) }}

            {{ $subcopy ?? '' }}
        </div>

        {{-- Footer --}}
        <div class="email-footer">
            <p style="margin:0 0 4px;">
                &copy; {{ date('Y') }} Kos Firabo. Semua hak dilindungi.
            </p>
            <p style="margin:0;">
                Jika kamu tidak merasa meminta ini, abaikan saja email ini.
            </p>
        </div>

    </div>
</body>
</html>