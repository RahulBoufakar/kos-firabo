<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami — Kos Firabo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
/* ── Navbar ────────────────────────────────────────────── */
.contact-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
    height: 64px;
    background: #fff;
    border-bottom: 1px solid var(--firabo-border);
    position: sticky;
    top: 0;
    z-index: 100;
}
.contact-nav__back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: var(--firabo-primary-dark);
    font-size: 1.1rem;
    text-decoration: none;
    transition: background 0.15s;
}
.contact-nav__back:hover {
    background: var(--firabo-primary-light);
    color: var(--firabo-primary-dark);
}
.contact-nav__brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1.2;
}
.contact-nav__brand-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--firabo-primary-dark);
}
.contact-nav__brand-sub {
    font-size: 11px;
    color: #9ca3af;
}
.contact-nav__cta {
    background: var(--firabo-primary-dark);
    color: #fff;
    text-decoration: none;
    padding: 0.45rem 1.25rem;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.15s;
}
.contact-nav__cta:hover {
    background: var(--firabo-primary);
    color: #fff;
}

/* ── Page ──────────────────────────────────────────────── */
.contact-page {
    min-height: calc(100vh - 64px);
    background: var(--firabo-primary-light);
    padding: 3rem 1.5rem 4rem;
}

/* ── Hero ──────────────────────────────────────────────── */
.contact-hero {
    text-align: center;
    margin-bottom: 2.5rem;
}
.contact-hero__title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--firabo-primary-dark);
    margin: 0 0 0.5rem;
}
.contact-hero__subtitle {
    color: #6b7280;
    font-size: 15px;
    margin: 0;
}

/* ── Grid ──────────────────────────────────────────────── */
.contact-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    max-width: 900px;
    margin: 0 auto;
}

/* ── Card ──────────────────────────────────────────────── */
.contact-card {
    background: #fff;
    border-radius: 16px;
    padding: 2rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    box-shadow: 0 2px 12px rgba(45, 122, 86, 0.07);
    border: 1px solid var(--firabo-border);
    transition: transform 0.2s, box-shadow 0.2s;
}
.contact-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(45, 122, 86, 0.12);
}
.contact-card__icon {
    width: 56px;
    height: 56px;
    background: var(--firabo-primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--firabo-primary);
    margin-bottom: 1rem;
}
.contact-card__title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.contact-card__value {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 1.25rem;
    flex: 1;
}
.contact-card__btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: 100%;
    justify-content: center;
    background: var(--firabo-primary-dark);
    color: #fff;
    text-decoration: none;
    padding: 0.65rem 1rem;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.15s;
}
.contact-card__btn:hover {
    background: var(--firabo-primary);
    color: #fff;
}

/* ── Responsive ────────────────────────────────────────── */
@media (max-width: 768px) {
    .contact-nav { padding: 0 1rem; }
    .contact-hero__title { font-size: 1.625rem; }
    .contact-grid { grid-template-columns: 1fr; max-width: 420px; }
    .contact-card { padding: 1.5rem 1.25rem; }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .contact-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 640px;
    }
    .contact-card:last-child {
        grid-column: 1 / -1;
        max-width: 300px;
        margin: 0 auto;
        width: 100%;
    }
}
    </style>
</head>
<body>

{{-- ── Navbar ─────────────────────────────────────────── --}}
<nav class="contact-nav">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('login') }}"
       class="contact-nav__back" aria-label="Kembali">
        <i class="bi bi-arrow-left"></i>
    </a>

    <div class="contact-nav__brand">
        <span class="contact-nav__brand-name">Kos Firabo</span>
        <span class="contact-nav__brand-sub">Management Portal</span>
    </div>

    @auth
        <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('penghuni.dashboard') }}"
           class="contact-nav__cta">
            Dashboard
        </a>
    @else
        <a href="{{ route('login') }}" class="contact-nav__cta">
            Masuk
        </a>
    @endauth
</nav>

{{-- ── Hero ───────────────────────────────────────────── --}}
<main class="contact-page">
    <div class="contact-hero">
        <h1 class="contact-hero__title">Contact Us</h1>
        <p class="contact-hero__subtitle">
            Hubungi tim manajemen kami untuk bantuan atau informasi lebih lanjut.
        </p>
    </div>

    {{-- ── Cards ─────────────────────────────────────────── --}}
    <div class="contact-grid">

        {{-- Telepon --}}
        <div class="contact-card">
            <div class="contact-card__icon">
                <i class="bi bi-telephone"></i>
            </div>
            <h2 class="contact-card__title">Phone Number</h2>
            <p class="contact-card__value">{{\App\Models\user::where('role', 'admin')->first()->no_wa}}</p>
            <a href="tel:+6281234567890" class="contact-card__btn">
                <i class="bi bi-telephone"></i>
                Call Now
            </a>
        </div>

        {{-- WhatsApp --}}
        <div class="contact-card">
            <div class="contact-card__icon">
                <i class="bi bi-chat-dots"></i>
            </div>
            <h2 class="contact-card__title">WhatsApp</h2>
            <p class="contact-card__value">{{\App\Models\user::where('role', 'admin')->first()->no_wa}}</p>
            <a href="https://wa.me/6281234567890?text=Halo%20Kos%20Firabo%2C%20saya%20ingin%20bertanya."
               target="_blank"
               rel="noopener noreferrer"
               class="contact-card__btn">
                <i class="bi bi-chat-dots"></i>
                Chat on WhatsApp
            </a>
        </div>

        {{-- Email --}}
        <div class="contact-card">
            <div class="contact-card__icon">
                <i class="bi bi-envelope"></i>
            </div>
            <h2 class="contact-card__title">Email</h2>
            <p class="contact-card__value">{{\App\Models\user::where('role', 'admin')->first()->email}}</p>
            <a href="mailto:bantuan@kosfirabo.com" class="contact-card__btn">
                <i class="bi bi-send"></i>
                Send Email
            </a>
        </div>

    </div>
</main>

</body>
</html>