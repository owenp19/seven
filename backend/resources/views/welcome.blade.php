<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SEVEN API — Documentación y estado de la plataforma</title>
  <meta name="description" content="SEVEN API conecta tours, restaurantes, posadas nativas y negocios de San Andrés bajo un contrato REST predecible y documentado.">

  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  <link rel="icon" type="image/png" href="{{ asset('images/brand/seven-isotipo.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/brand/seven-isotipo.png') }}">


  <!-- SEVEN design fonts (misma combinación que la app) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ============================================================
       SEVEN DESIGN TOKENS
       ============================================================ */
    :root {
      --deep-sea:        #0B2D4A;
      --deep-sea-rgb:    11, 45, 74;
      --ocean-blue:      #0077B6;
      --ocean-blue-rgb:  0, 119, 182;
      --tropical-tide:   #00D1C5;
      --tropical-tide-rgb: 0, 209, 197;
      --lagoon-blue:     #1CA7EC;
      --palm-green:      #1F8A5C;
      --soft-coral:      #FF7A59;
      --sun-gold:        #FF9E45;
      --arena:           #E8DCC8;

      --gray-50:  #F4F7FA;
      --gray-100: #E7ECF1;
      --gray-200: #D4DCE4;
      --gray-300: #B8C4CF;
      --gray-400: #93A4B3;
      --gray-500: #6E8296;
      --gray-600: #566B7E;
      --gray-700: #41566A;
      --gray-800: #2E4253;
      --gray-900: #1C2E3D;

      --font-display: 'Oswald', system-ui, sans-serif;
      --font-body: 'Poppins', system-ui, -apple-system, sans-serif;

      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-xl: 20px;
      --radius-pill: 999px;

      --shadow-soft: 0 2px 10px rgba(var(--deep-sea-rgb), 0.06);
      --shadow-card: 0 6px 20px rgba(var(--deep-sea-rgb), 0.08);
      --shadow-raised: 0 12px 28px rgba(var(--deep-sea-rgb), 0.16);

      --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
    }

    /* ============================================================
       BASE
       ============================================================ */
    *, *::before, *::after { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      margin: 0;
      font-family: var(--font-body);
      font-size: 16px;
      line-height: 1.55;
      color: var(--gray-900);
      background: var(--gray-50);
      -webkit-font-smoothing: antialiased;
    }

    img { max-width: 100%; display: block; }

    a { color: var(--ocean-blue); text-decoration: none; }

    .container {
      width: 100%;
      max-width: 1180px;
      margin-inline: auto;
      padding-inline: 24px;
    }

    .icon { display: inline-block; width: 24px; height: 24px; flex: none; }

    .icon--sm { width: 18px; height: 18px; }

    /* ============================================================
       ANIMACIONES DE ENTRADA
       ============================================================ */
    [data-reveal] {
      opacity: 0;
      animation: reveal 700ms var(--ease-out) forwards;
    }

    [data-reveal="1"] { animation-delay: 120ms; }
    [data-reveal="2"] { animation-delay: 240ms; }
    [data-reveal="3"] { animation-delay: 360ms; }
    [data-reveal="4"] { animation-delay: 480ms; }
    [data-reveal="5"] { animation-delay: 600ms; }

    @keyframes reveal {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ============================================================
       HEADER
       ============================================================ */
    .header {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(var(--deep-sea-rgb), 0.94);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .header__inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      min-height: 64px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .brand__logo { width: 34px; height: 34px; object-fit: contain; }

    .brand__name {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: 21px;
      letter-spacing: 0.6px;
      color: #fff;
      text-transform: uppercase;
    }

    .brand__name span { color: var(--tropical-tide); }

    .header__actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 7px 13px;
      border-radius: var(--radius-pill);
      font-size: 13px;
      font-weight: 600;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.16);
      background: rgba(255, 255, 255, 0.05);
    }

    .pill .dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--tropical-tide);
      box-shadow: 0 0 0 3px rgba(var(--tropical-tide-rgb), 0.25);
    }

    .header__link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: rgba(255, 255, 255, 0.9);
      font-size: 14px;
      font-weight: 600;
      padding: 7px 14px;
      border-radius: var(--radius-pill);
      background: var(--ocean-blue);
      transition: background 180ms ease, transform 180ms ease;
    }

    .header__link:hover { background: #005b8c; transform: translateY(-1px); }

    /* ============================================================
       HERO
       ============================================================ */
    .hero {
      position: relative;
      overflow: hidden;
      background: var(--deep-sea);
    }

    .hero__bg {
      position: absolute;
      inset: 0;
      background-image: url("{{ asset('images/sai-bg.jpg') }}");
      background-size: cover;
      background-position: center;
      transform: scale(1.01);
    }

    .hero__overlay {
      position: absolute;
      inset: 0;
      background: rgba(var(--deep-sea-rgb), 0.74);
    }

    .hero__inner {
      position: relative;
      z-index: 2;
      display: grid;
      gap: 44px;
      padding-block: 72px 88px;
    }

    .hero__badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: var(--radius-pill);
      border: 1px solid rgba(var(--tropical-tide-rgb), 0.5);
      background: rgba(var(--tropical-tide-rgb), 0.12);
      color: var(--tropical-tide);
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 1.4px;
      text-transform: uppercase;
      margin-bottom: 22px;
    }

    .hero__title {
      margin: 0 0 18px;
      font-family: var(--font-display);
      font-weight: 700;
      font-size: clamp(42px, 8vw, 74px);
      line-height: 0.98;
      letter-spacing: -1.8px;
      color: #fff;
      text-wrap: balance;
      text-shadow: 0 3px 14px rgba(var(--deep-sea-rgb), 0.5);
    }

    .hero__title em {
      font-style: normal;
      color: var(--tropical-tide);
    }

    .hero__line {
      width: 54px;
      height: 3px;
      border-radius: var(--radius-pill);
      background: rgba(255, 255, 255, 0.85);
      margin: 0 0 14px;
      animation: grow 600ms var(--ease-out) 400ms both;
      transform-origin: left center;
    }

    @keyframes grow {
      from { opacity: 0; transform: scaleX(0); }
      to   { opacity: 1; transform: scaleX(1); }
    }

    .hero__description {
      margin: 0 0 30px;
      max-width: 560px;
      color: rgba(255, 255, 255, 0.94);
      font-size: clamp(16px, 2.4vw, 19px);
      line-height: 1.5;
      font-weight: 400;
      text-shadow: 0 2px 8px rgba(var(--deep-sea-rgb), 0.4);
    }

    .hero__cta {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 44px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      min-height: 54px;
      padding: 0 26px;
      border-radius: var(--radius-pill);
      font-family: var(--font-body);
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid transparent;
      transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease;
    }

    .btn .icon, .btn svg { margin-right: 4px; }

    .btn--primary {
      background: var(--ocean-blue);
      color: #fff;
      box-shadow: 0 10px 32px rgba(var(--ocean-blue-rgb), 0.4);
    }

    .btn--primary:hover {
      background: #005b8c;
      transform: translateY(-2px);
      box-shadow: 0 14px 40px rgba(var(--ocean-blue-rgb), 0.5);
    }

    .btn--ghost {
      background: rgba(255, 255, 255, 0.06);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.28);
    }

    .btn--ghost:hover { background: rgba(255, 255, 255, 0.12); transform: translateY(-2px); }

    .btn--arrow svg { transition: transform 200ms ease; }
    .btn--arrow:hover svg { transform: translateX(4px); }

    /* Terminal (frontend "interview" style) */
    .terminal {
      background: #081d30;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-raised);
      overflow: hidden;
      max-width: 560px;
    }

    .terminal__bar {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .terminal__dot { width: 11px; height: 11px; border-radius: 50%; }
    .terminal__dot--a { background: #FF5F57; }
    .terminal__dot--b { background: #FEBC2E; }
    .terminal__dot--c { background: #28C840; }

    .terminal__title {
      margin-left: 8px;
      color: rgba(255, 255, 255, 0.55);
      font-size: 12.5px;
      font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace;
    }

    .terminal__body {
      padding: 18px 20px;
      font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace;
      font-size: 13.5px;
      line-height: 1.7;
      overflow-x: auto;
      color: #dbe9f5;
    }

    .terminal__body .prompt { color: var(--tropical-tide); }

    .terminal__body .key { color: var(--lagoon-blue); }
    .terminal__body .str { color: var(--soft-coral); }
    .terminal__body .bool { color: var(--sun-gold); }
    .terminal__body .dim { color: rgba(219, 233, 245, 0.5); }

    /* ============================================================
       SECCIONES (estado del servicio)
       ============================================================ */
    .section { padding-block: 72px; }

    .section--start { padding-top: 60px; }

    .section__head { margin-bottom: 40px; }

    .section__eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--ocean-blue);
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 1.6px;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .section__title {
      margin: 0;
      font-family: var(--font-display);
      font-weight: 600;
      font-size: clamp(28px, 4vw, 40px);
      line-height: 1.05;
      letter-spacing: -0.6px;
      color: var(--deep-sea);
      text-wrap: balance;
    }

    .section__sub {
      margin: 10px 0 0;
      max-width: 640px;
      color: var(--gray-500);
      font-size: 15.5px;
    }

    /* Runtime cards */
    .status-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .status-card {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 20px;
      background: #fff;
      border: 1px solid var(--gray-100);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-soft);
    }

    .status-card__icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      border-radius: var(--radius-md);
      background: rgba(var(--ocean-blue-rgb), 0.1);
      color: var(--ocean-blue);
      flex: none;
    }

    .status-card__label {
      font-size: 13.5px;
      font-weight: 500;
      color: var(--gray-500);
      margin-bottom: 2px;
    }

    .status-card__value {
      font-family: var(--font-display);
      font-weight: 600;
      font-size: 20px;
      color: var(--deep-sea);
    }

    .status-card__value .ok {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--palm-green);
    }

    .status-card__value .ok .dot {
      width: 9px; height: 9px; border-radius: 50%;
      background: var(--palm-green);
      box-shadow: 0 0 0 4px rgba(31, 138, 92, 0.15);
    }

    /* Stats strip */
    .stats-strip {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-top: 20px;
    }

    .stat-chip {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: #fff;
      border: 1px solid var(--gray-100);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-soft);
    }

    .stat-chip__icon { display: flex; color: var(--gray-400); }

    .stat-chip__num {
      font-family: var(--font-display);
      font-weight: 600;
      font-size: 22px;
      line-height: 1;
      color: var(--deep-sea);
    }

    .stat-chip__name { font-size: 13px; color: var(--gray-500); }

    .stat-chip[data-color="tide"] .stat-chip__icon { color: var(--tropical-tide); }
    .stat-chip[data-color="blue"] .stat-chip__icon { color: var(--ocean-blue); }
    .stat-chip[data-color="coral"] .stat-chip__icon { color: var(--soft-coral); }
    .stat-chip[data-color="green"] .stat-chip__icon { color: var(--palm-green); }
    .stat-chip[data-color="gold"] .stat-chip__icon { color: var(--sun-gold); }
    .stat-chip[data-color="lagoon"] .stat-chip__icon { color: var(--lagoon-blue); }

    /* ============================================================
       TARJETAS DE RECURSOS
       ============================================================ */
    .resources-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 16px;
    }

    .resource-card {
      display: flex;
      flex-direction: column;
      gap: 16px;
      padding: 24px;
      background: #fff;
      border: 1px solid var(--gray-100);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-card);
      transition: transform 200ms var(--ease-out), box-shadow 200ms ease;
    }

    .resource-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-raised);
    }

    .resource-card__top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .resource-card__icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      border-radius: var(--radius-md);
      flex: none;
    }

    .resource-card[data-color="tide"]  .resource-card__icon { background: rgba(var(--tropical-tide-rgb), 0.12); color: var(--deep-sea); }
    .resource-card[data-color="blue"]  .resource-card__icon { background: rgba(var(--ocean-blue-rgb), 0.10); color: var(--ocean-blue); }
    .resource-card[data-color="coral"] .resource-card__icon { background: rgba(255, 122, 89, 0.10); color: var(--soft-coral); }
    .resource-card[data-color="green"] .resource-card__icon { background: rgba(31, 138, 92, 0.10); color: var(--palm-green); }
    .resource-card[data-color="gold"]  .resource-card__icon { background: rgba(255, 158, 69, 0.12); color: var(--sun-gold); }
    .resource-card[data-color="lagoon"].resource-card__icon { background: rgba(28, 167, 236, 0.10); color: var(--lagoon-blue); }

    .resource-card__count {
      font-family: var(--font-display);
      font-weight: 600;
      font-size: 17px;
      color: var(--deep-sea);
      padding: 5px 13px;
      border-radius: var(--radius-pill);
      background: var(--gray-50);
      border: 1px solid var(--gray-100);
      white-space: nowrap;
    }

    .resource-card__name {
      margin: 0;
      font-family: var(--font-display);
      font-weight: 600;
      font-size: 21px;
      letter-spacing: 0.2px;
      color: var(--deep-sea);
    }

    .resource-card__desc {
      margin: 6px 0 0;
      font-size: 14.5px;
      color: var(--gray-500);
    }

    .resource-card__route {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 16px;
      border-radius: var(--radius-md);
      background: var(--gray-50);
      border: 1px solid var(--gray-100);
    }

    .resource-card__method {
      font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace;
      font-size: 12px;
      font-weight: 700;
      color: #fff;
      background: var(--ocean-blue);
      padding: 4px 9px;
      border-radius: 6px;
      letter-spacing: 0.5px;
    }

    .resource-card__path {
      font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace;
      font-size: 14px;
      color: var(--gray-700);
      overflow-x: auto;
      white-space: nowrap;
    }

    .resource-card__go { display: flex; color: var(--ocean-blue); transition: transform 180ms ease; }

    .resource-card:hover .resource-card__go { transform: translate(3px, -3px); }

    .resource-card--booking .resource-card__icon { background: rgba(var(--deep-sea-rgb), 0.08); color: var(--deep-sea); }

    /* ============================================================
       BANNER DE DOCUMENTACIÓN
       ============================================================ */
    .docs-banner {
      position: relative;
      overflow: hidden;
      background: var(--deep-sea);
      border-radius: var(--radius-xl);
      padding: 40px 32px;
      color: #fff;
      box-shadow: var(--shadow-raised);
    }

    .docs-banner__grid {
      display: flex;
      flex-direction: column;
      gap: 28px;
    }

    .docs-banner__icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      border-radius: var(--radius-md);
      background: rgba(var(--tropical-tide-rgb), 0.14);
      color: var(--tropical-tide);
      margin-bottom: 20px;
    }

    .docs-banner__title {
      margin: 0 0 10px;
      font-family: var(--font-display);
      font-weight: 600;
      font-size: clamp(26px, 4vw, 36px);
      line-height: 1.05;
      letter-spacing: -0.4px;
    }

    .docs-banner__text {
      margin: 0 0 26px;
      max-width: 460px;
      color: rgba(255, 255, 255, 0.85);
      font-size: 15.5px;
    }

    .docs-banner__links { display: flex; flex-wrap: wrap; gap: 12px; }

    .btn--accent {
      background: var(--tropical-tide);
      color: var(--deep-sea);
      font-weight: 700;
    }

    .btn--accent:hover { background: #00bdb3; transform: translateY(-2px); }

    .btn--inverse {
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.28);
    }

    .btn--inverse:hover { background: rgba(255, 255, 255, 0.14); transform: translateY(-2px); }

    .docs-banner__meta {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 22px;
      color: rgba(255, 255, 255, 0.55);
      font-size: 13px;
    }

    /* ============================================================
       ENVELOPE DE RESPUESTA
       ============================================================ */
    .envelope {
      display: grid;
      gap: 32px;
      align-items: center;
    }

    .feature-list { display: grid; gap: 18px; margin-top: 24px; }

    .feature {
      display: flex;
      gap: 14px;
      align-items: flex-start;
    }

    .feature__icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: var(--radius-md);
      flex: none;
      background: rgba(var(--ocean-blue-rgb), 0.1);
      color: var(--ocean-blue);
    }

    .feature__title { font-weight: 600; color: var(--deep-sea); font-size: 15.5px; }

    .feature__text { margin: 2px 0 0; font-size: 14px; color: var(--gray-500); }

    .code-block {
      background: #081d30;
      border: 1px solid var(--gray-800);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-card);
    }

    .code-block__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 18px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      color: rgba(255, 255, 255, 0.55);
      font-size: 12.5px;
      font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace;
    }

    .code-block__tag {
      font-size: 11px;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 6px;
      background: rgba(var(--tropical-tide-rgb), 0.16);
      color: var(--tropical-tide);
      letter-spacing: 0.5px;
    }

    .code {
      padding: 20px 22px;
      margin: 0;
      overflow-x: auto;
      font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace;
      font-size: 13.5px;
      line-height: 1.7;
      color: #dbe9f5;
    }

    .code .key { color: var(--lagoon-blue); }
    .code .str { color: var(--soft-coral); }
    .code .bool { color: var(--sun-gold); }
    .code .dim { color: rgba(219, 233, 245, 0.5); }
    .code .punct { color: rgba(219, 233, 245, 0.8); }

    .code-row { display: flex; gap: 10px; }
    .code-row .ln {
      color: rgba(219, 233, 245, 0.28);
      text-align: right;
      width: 22px;
      flex: none;
      user-select: none;
    }

    /* Códigos HTTP */
    .codes {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    .code-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      border-radius: var(--radius-pill);
      background: #fff;
      border: 1px solid var(--gray-100);
      box-shadow: var(--shadow-soft);
      font-size: 13px;
      font-weight: 500;
      color: var(--gray-700);
    }

    .code-pill b { font-family: ui-monospace, 'Cascadia Code', 'SF Mono', Consolas, monospace; color: var(--deep-sea); }

    /* ============================================================
       FOOTER
       ============================================================ */
    .footer {
      background: var(--deep-sea);
      color: rgba(255, 255, 255, 0.72);
      padding-block: 48px 40px;
    }

    .footer__inner {
      display: grid;
      gap: 32px;
    }

    .footer__brand { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .footer__logo { width: 120px; height: auto; }

    .footer__tagline { margin: 0 0 8px; font-size: 14.5px; color: rgba(255, 255, 255, 0.6); }

    .footer__links {
      display: flex;
      flex-wrap: wrap;
      gap: 8px 22px;
      margin: 0;
      padding: 0;
      list-style: none;
    }

    .footer__links a {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      color: rgba(255, 255, 255, 0.75);
      font-size: 14px;
      font-weight: 500;
    }

    .footer__links a:hover { color: var(--tropical-tide); }

    .footer__stack {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .stack-chip {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 12px;
      border-radius: var(--radius-pill);
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: rgba(255, 255, 255, 0.8);
      font-size: 13px;
      font-weight: 500;
    }

    .footer__bottom {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-top: 34px;
      padding-top: 22px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.45);
      font-size: 13.5px;
    }

    .footer__madeby { display: inline-flex; align-items: center; gap: 7px; }

    .footer__madeby .heart { color: var(--soft-coral); }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (min-width: 640px) {
      .resources-grid { grid-template-columns: repeat(2, 1fr); }
      .status-grid { grid-template-columns: repeat(2, 1fr); }
      .stats-strip { grid-template-columns: repeat(3, 1fr); }
    }

    @media (min-width: 1024px) {
      .hero__inner {
        grid-template-columns: 1fr 1fr;
        align-items: center;
        gap: 64px;
        padding-block: 96px 120px;
      }

      .status-grid { grid-template-columns: repeat(2, 1fr); }
      .stats-strip { grid-template-columns: repeat(3, 1fr); }
      .resources-grid { grid-template-columns: repeat(3, 1fr); }
      .docs-banner__grid { flex-direction: row; align-items: center; justify-content: space-between; }
      .docs-banner__title { font-size: 36px; }
      .envelope { grid-template-columns: 1fr 1.1fr; }
      .footer__inner { grid-template-columns: 1.4fr 1fr 1.2fr; align-items: start; }
    }

    @media (max-width: 640px) {
      .hero__inner { gap: 32px; padding-block: 48px 64px; }
      .hero__badge { font-size: 12px; padding: 7px 13px; margin-bottom: 18px; }
      .hero__title { font-size: clamp(34px, 10vw, 48px); }
      .terminal__body { font-size: 12.5px; padding: 16px; }
      .section { padding-block: 56px; }
      .section--start { padding-top: 44px; }
      .section__head { margin-bottom: 28px; }
      .stats-strip { gap: 10px; margin-top: 16px; }
      .status-card { padding: 16px; }
      .resource-card { padding: 18px; gap: 14px; }
      .resource-card__count { font-size: 14.5px; }
      .resource-card__name { font-size: 19px; }
      .resource-card__desc { font-size: 14px; }
      .resource-card__route {
        grid-template-columns: auto minmax(0, 1fr) auto;
        justify-items: start;
        gap: 8px;
        padding: 10px 12px;
      }
      .resource-card__path { font-size: 12.5px; }
      .btn { min-height: 50px; padding: 0 22px; }
    }

    @media (max-width: 560px) {
      .header__inner { min-height: 56px; }
      .brand__logo { width: 30px; height: 30px; }
      .brand__name { font-size: 18px; }
      .header__actions { gap: 8px; }
      .header__link { gap: 6px; padding: 6px 11px; font-size: 13px; }
      .pill { display: none; }
    }

    @media (max-width: 480px) {
      .container { padding-inline: 16px; }
      .terminal__title { font-size: 11.5px; }
      .status-grid { gap: 10px; }
      .status-card { gap: 10px; }
      .status-card__icon { width: 40px; height: 40px; }
    }

    /* ============================================================
       REDUCED MOTION
       ============================================================ */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
        animation-delay: 0ms !important;
      }
    }
  </style>
</head>

<body>

  <!-- ============================================================
       SPRITE DE ICONOS (Ionicons, inline, sin dependencias)
       ============================================================ -->
  <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
    <symbol id="i-layers" viewBox="0 0 512 512"><path d="m434.8 137.65-149.36-68.1c-16.19-7.4-42.69-7.4-58.88 0L77.3 137.65c-17.6 8-17.6 21.09 0 29.09l148 67.5c16.89 7.7 44.69 7.7 61.58 0l148-67.5c17.52-8 17.52-21.1-.08-29.09M160 308.52l-82.7 37.11c-17.6 8-17.6 21.1 0 29.1l148 67.5c16.89 7.69 44.69 7.69 61.58 0l148-67.5c17.6-8 17.6-21.1 0-29.1l-79.94-38.47" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="m160 204.48-82.8 37.16c-17.6 8-17.6 21.1 0 29.1l148 67.49c16.89 7.7 44.69 7.7 61.58 0l148-67.49c17.7-8 17.7-21.1.1-29.1L352 204.48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-boat" viewBox="0 0 512 512"><path d="M461.93 261.05c-2-4.76-6.71-7.83-11.67-9.49l-187.18-74.48a23.8 23.8 0 0 0-14.17 0l-187 74.52c-5 1.56-9.83 4.77-11.81 9.53s-2.94 9.37-1 15.08l46.53 119.15a7.46 7.46 0 0 0 7.47 4.64c26.69-1.68 50.31-15.23 68.38-32.5a7.66 7.66 0 0 1 10.49 0C201.29 386 227 400 256 400s54.56-14 73.88-32.54a7.67 7.67 0 0 1 10.5 0c18.07 17.28 41.69 30.86 68.38 32.54a7.45 7.45 0 0 0 7.46-4.61l46.7-119.16c1.98-4.78.99-10.41-.99-15.18Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="M416 473.14a6.84 6.84 0 0 0-3.56-6c-27.08-14.55-51.77-36.82-62.63-48a10.05 10.05 0 0 0-12.72-1.51c-50.33 32.42-111.61 32.44-161.95.05a10.09 10.09 0 0 0-12.82 1.56c-10.77 11.28-35.19 33.3-62.43 47.75a7.15 7.15 0 0 0-3.89 5.73 6.73 6.73 0 0 0 7.92 7.15c20.85-4.18 41-13.68 60.2-23.83a8.71 8.71 0 0 1 8-.06A185.14 185.14 0 0 0 340 456a8.82 8.82 0 0 1 8.09.06c19.1 10 39.22 19.59 60 23.8a6.72 6.72 0 0 0 7.95-6.71Z"/><path d="M320 96V72a24.07 24.07 0 0 0-24-24h-80a24.07 24.07 0 0 0-24 24v24M416 233v-89a48.14 48.14 0 0 0-48-48H144a48.14 48.14 0 0 0-48 48v92M256 183.6v212.85" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-restaurant" viewBox="0 0 512 512"><path d="m57.49 47.74 368.43 368.43a37.28 37.28 0 0 1 0 52.72 37.29 37.29 0 0 1-52.72 0l-90-91.55a32 32 0 0 1-9.2-22.43v-5.53a32 32 0 0 0-9.52-22.78l-11.62-10.73a32 32 0 0 0-29.8-7.44 48.53 48.53 0 0 1-46.56-12.63l-85.43-85.44C40.39 159.68 21.74 83.15 57.49 47.74Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><path d="m400 32-77.25 77.25A64 64 0 0 0 304 154.51v14.86a16 16 0 0 1-4.69 11.32L288 192M320 224l11.31-11.31a16 16 0 0 1 11.32-4.69h14.86a64 64 0 0 0 45.26-18.75L480 112M440 72l-80 80M200 368l-99.72 100.28a40 40 0 0 1-56.56 0h0a40 40 0 0 1 0-56.56L128 328" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-home" viewBox="0 0 512 512"><path d="M80 212v236a16 16 0 0 0 16 16h96V328a24 24 0 0 1 24-24h80a24 24 0 0 1 24 24v136h96a16 16 0 0 0 16-16V212" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M480 256 266.89 52c-5-5.28-16.69-5.34-21.78 0L32 256M400 179V64h-48v69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-cafe" viewBox="0 0 512 512"><path d="M368 80h64a16 16 0 0 1 16 16v34a46 46 0 0 1-46 46h-34M96 80h272v192a80 80 0 0 1-80 80H176a80 80 0 0 1-80-80zM64 416h336" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-storefront" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M448 448V240M64 240v208M382.47 48H129.53c-21.79 0-41.47 12-49.93 30.46L36.3 173c-14.58 31.81 9.63 67.85 47.19 69h2c31.4 0 56.85-25.18 56.85-52.23 0 27 25.46 52.23 56.86 52.23s56.8-23.38 56.8-52.23c0 27 25.45 52.23 56.85 52.23s56.86-23.38 56.86-52.23c0 28.85 25.45 52.23 56.85 52.23h1.95c37.56-1.17 61.77-37.21 47.19-69l-43.3-94.54C423.94 60 404.26 48 382.47 48M32 464h448M136 288h80a24 24 0 0 1 24 24v88h0-128 0v-88a24 24 0 0 1 24-24M288 464V312a24 24 0 0 1 24-24h64a24 24 0 0 1 24 24v152"/></symbol>
    <symbol id="i-bed" viewBox="0 0 512 512"><path d="M384 240H96V136a40.12 40.12 0 0 1 40-40h240a40.12 40.12 0 0 1 40 40v104ZM48 416V304a64.19 64.19 0 0 1 64-64h288a64.19 64.19 0 0 1 64 64v112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M48 416v-8a24.07 24.07 0 0 1 24-24h368a24.07 24.07 0 0 1 24 24v8M112 240v-16a32.09 32.09 0 0 1 32-32h80a32.09 32.09 0 0 1 32 32v16M256 240v-16a32.09 32.09 0 0 1 32-32h80a32.09 32.09 0 0 1 32 32v16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-compass" viewBox="0 0 512 512"><path d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="m350.67 150.93-117.2 46.88a64 64 0 0 0-35.66 35.66l-46.88 117.2a8 8 0 0 0 10.4 10.4l117.2-46.88a64 64 0 0 0 35.66-35.66l46.88-117.2a8 8 0 0 0-10.4-10.4M256 280a24 24 0 1 1 24-24 24 24 0 0 1-24 24"/></symbol>
    <symbol id="i-sparkles" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M259.92 262.91 216.4 149.77a9 9 0 0 0-16.8 0l-43.52 113.14a9 9 0 0 1-5.17 5.17L37.77 311.6a9 9 0 0 0 0 16.8l113.14 43.52a9 9 0 0 1 5.17 5.17l43.52 113.14a9 9 0 0 0 16.8 0l43.52-113.14a9 9 0 0 1 5.17-5.17l113.14-43.52a9 9 0 0 0 0-16.8l-113.14-43.52a9 9 0 0 1-5.17-5.17M108 68 88 16 68 68 16 88l52 20 20 52 20-52 52-20zM426.67 117.33 400 48l-26.67 69.33L304 144l69.33 26.67L400 240l26.67-69.33L496 144z"/></symbol>
    <symbol id="i-gift" viewBox="0 0 512 512"><path d="M256 104v56h56a56 56 0 1 0-56-56ZM256 104v56h-56a56 56 0 1 1 56-56Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32"/><rect width="384" height="112" x="64" y="160" rx="32" ry="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M416 272v144a48 48 0 0 1-48 48H144a48 48 0 0 1-48-48V272M256 160v304" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-image" viewBox="0 0 512 512"><rect width="416" height="352" x="48" y="80" rx="48" ry="48" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><circle cx="336" cy="176" r="32" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="m304 335.79-90.66-90.49a32 32 0 0 0-43.87-1.3L48 352M224 432l123.34-123.34a32 32 0 0 1 43.11-2L464 368" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-lock" viewBox="0 0 512 512"><path d="M336 208v-95a80 80 0 0 0-160 0v95" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><rect width="320" height="272" x="96" y="208" rx="48" ry="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-book" viewBox="0 0 512 512"><path d="M256 160c16-63.16 76.43-95.41 208-96a15.94 15.94 0 0 1 16 16v288a16 16 0 0 1-16 16c-128 0-177.45 25.81-208 64-30.37-38-80-64-208-64-9.88 0-16-8.05-16-17.93V80a15.94 15.94 0 0 1 16-16c131.57.59 192 32.84 208 96M256 160v288" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-doc" viewBox="0 0 512 512"><path d="M416 221.25V416a48 48 0 0 1-48 48H144a48 48 0 0 1-48-48V96a48 48 0 0 1 48-48h98.75a32 32 0 0 1 22.62 9.37l141.26 141.26a32 32 0 0 1 9.37 22.62Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><path d="M256 56v120a32 32 0 0 0 32 32h120M176 288h160M176 368h160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-code" viewBox="0 0 512 512"><path d="M160 368 32 256l128-112M352 368l128-112-128-112M304 96l-96 320" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-check" viewBox="0 0 512 512"><path d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="M352 176 217.6 336 160 272" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 512 512"><path d="m268 112 144 144-144 144M392 256H100" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="48"/></symbol>
    <symbol id="i-pulse" viewBox="0 0 512 512"><path d="M48 320h64l64-256 64 384 64-224 32 96h64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><circle cx="432" cy="320" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-server" viewBox="0 0 512 512"><ellipse cx="256" cy="112" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" rx="176" ry="80"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M432 112v288c0 44.183-78.798 80-176 80S80 444.183 80 400V112"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M432 256c0 44.183-78.798 80-176 80S80 300.183 80 256"/></symbol>
    <symbol id="i-shield" viewBox="0 0 512 512"><path d="M336 176 225.2 304 176 255.8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M463.1 112.37C373.68 96.33 336.71 84.45 256 48c-80.71 36.45-117.68 48.33-207.1 64.37C32.7 369.13 240.58 457.79 256 464c15.42-6.21 223.3-94.87 207.1-351.63" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-terminal" viewBox="0 0 512 512"><rect width="448" height="416" x="32" y="48" rx="48" ry="48" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><path d="m96 112 80 64-80 64M192 240h64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-globe" viewBox="0 0 512 512"><path d="M256 48C141.13 48 48 141.13 48 256s93.13 208 208 208 208-93.13 208-208S370.87 48 256 48Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="M256 48c-58.07 0-112.67 93.13-112.67 208S197.93 464 256 464s112.67-93.13 112.67-208S314.07 48 256 48Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="M117.33 117.33c38.24 27.15 86.38 43.34 138.67 43.34s100.43-16.19 138.67-43.34M394.67 394.67c-38.24-27.15-86.38-43.34-138.67-43.34s-100.43 16.19-138.67 43.34" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M256 48v416M464 256H48" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/></symbol>
    <symbol id="i-rocket" viewBox="0 0 512 512"><path d="M461.81 53.81a4.4 4.4 0 0 0-3.3-3.39c-54.38-13.3-180 34.09-248.13 102.17a295 295 0 0 0-33.09 39.08c-21-1.9-42-.3-59.88 7.5-50.49 22.2-65.18 80.18-69.28 105.07a9 9 0 0 0 9.8 10.4l81.07-8.9a180 180 0 0 0 1.1 18.3 18.15 18.15 0 0 0 5.3 11.09l31.39 31.39a18.15 18.15 0 0 0 11.1 5.3 180 180 0 0 0 18.19 1.1l-8.89 81a9 9 0 0 0 10.39 9.79c24.9-4 83-18.69 105.07-69.17 7.8-17.9 9.4-38.79 7.6-59.69a294 294 0 0 0 39.19-33.09c68.38-68 115.47-190.86 102.37-247.95M298.66 213.67a42.7 42.7 0 1 1 60.38 0 42.65 42.65 0 0 1-60.38 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M109.64 352a45.06 45.06 0 0 0-26.35 12.84C65.67 382.52 64 448 64 448s65.52-1.67 83.15-19.31A44.73 44.73 0 0 0 160 402.32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-wifi" viewBox="0 0 512 512"><path d="M332.41 310.59a48 48 0 0 1-152.82 0M221.9 416H290m0-105.41a110.08 110.08 0 0 1 153.1 10M318.67 294.5a110.08 110.08 0 0 1 111.28 27.44" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M66.56 235.32v.19C146.42 157.5 200 115 256 115s109.58 42.5 189.44 120.46M119.61 279.26c44.9-53.71 81.26-80.26 136.39-80.26s91.49 26.55 136.39 80.26M153.19 321.19c37.36-42.3 64.85-62.19 102.81-62.19 37.66 0 65.94 19.95 102.81 62.19" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></symbol>
    <symbol id="i-people" viewBox="0 0 512 512"><path d="M402 168c-2.93 40.67-33.1 72-66 72s-63.12-31.32-66-72c-3-42.31 26.37-72 66-72s69 30.46 66 72" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M336 304c-65.17 0-127.84 32.37-143.54 95.41-2.08 8.34 3.15 16.59 11.72 16.59h263.65c8.57 0 13.77-8.25 11.72-16.59C463.85 335.36 401.18 304 336 304Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="M200 185.94c-2.34 32.48-26.72 58.06-53 58.06s-50.7-25.57-53-58.06C91.61 152.15 115.34 128 147 128s55.39 24.77 53 57.94" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M206 306c-18.05-8.27-37.93-11.45-59-11.45-52 0-102.1 25.85-114.65 76.2-1.65 6.66 2.53 13.25 9.37 13.25H154" fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32"/></symbol>
  </svg>

  <!-- ============================================================
       HEADER
       ============================================================ -->
  <header class="header">
    <div class="container header__inner">
      <a href="{{ url('/') }}" class="brand" aria-label="SEVEN API — inicio">
        <img class="brand__logo" src="{{ asset('images/brand/seven-isotipo-blanco.png') }}" alt="">
        <span class="brand__name">SEVEN<span> API</span></span>
      </a>

      <div class="header__actions">
        <span class="pill"><span class="dot"></span>Operativa</span>
        <a class="header__link" href="{{ $api['docs_ui'] }}">
          <svg class="icon icon--sm"><use href="#i-book"/></svg>
          Documentación
        </a>
      </div>
    </div>
  </header>

  <!-- ============================================================
       HERO
       ============================================================ -->
  <section class="hero">
    <div class="hero__bg" aria-hidden="true"></div>
    <div class="hero__overlay" aria-hidden="true"></div>

    <div class="container hero__inner">
      <div>
        <span class="hero__badge" data-reveal>
          <svg class="icon icon--sm"><use href="#i-layers"/></svg>
          API v1 &middot; San Andrés Islas
        </span>

        <h1 class="hero__title" data-reveal="1">
          La isla entera,<br>en una <em>sola API</em>.
        </h1>

        <div class="hero__line"></div>

        <p class="hero__description" data-reveal="2">
          SEVEN conecta tours, restaurantes, posadas nativas, panaderías y
          negocios locales bajo un contrato REST predecible, documentado y
          listo para producción.
        </p>

        <div class="hero__cta" data-reveal="3">
          <a class="btn btn--primary btn--arrow" href="{{ $api['docs_ui'] }}">
            Ver Swagger
            <svg class="icon icon--sm"><use href="#i-arrow"/></svg>
          </a>
          <a class="btn btn--ghost" href="#recursos">
            Explorar endpoints
          </a>
        </div>

        <div class="terminal" data-reveal="4">
          <div class="terminal__bar">
            <span class="terminal__dot terminal__dot--a"></span>
            <span class="terminal__dot terminal__dot--b"></span>
            <span class="terminal__dot terminal__dot--c"></span>
            <span class="terminal__title">SEVEN CLI &mdash; quickstart</span>
          </div>
          <div class="terminal__body">
            <div><span class="prompt">$</span> curl {{ $api['root'] }}/tours</div>
            <div><br></div>
            <div><span class="punct">{</span></div>
            <div>&nbsp;&nbsp;<span class="key">"success"</span>: <span class="bool">true</span><span class="punct">,</span></div>
            <div>&nbsp;&nbsp;<span class="key">"data"</span>: <span class="punct">[</span> <span class="dim">&hellip; 24 tours aprobados &hellip;</span> <span class="punct">]</span></div>
            <div>&nbsp;&nbsp;<span class="key">"meta"</span>: <span class="punct">{</span> <span class="key">"current_page"</span>: <span class="bool">1</span> <span class="punct">}</span></div>
            <div><span class="punct">}</span></div>
          </div>
        </div>
      </div>

      <!-- Estado del servicio -->
      <div>
        <div class="status-grid" data-reveal="2">
          <div class="status-card">
            <div class="status-card__icon"><svg class="icon"><use href="#i-pulse"/></svg></div>
            <div>
              <div class="status-card__label">Estado</div>
              <div class="status-card__value"><span class="ok"><span class="dot"></span>Operativa</span></div>
            </div>
          </div>

          <div class="status-card">
            <div class="status-card__icon"><svg class="icon"><use href="#i-code"/></svg></div>
            <div>
              <div class="status-card__label">Versión</div>
              <div class="status-card__value">v{{ $api['version'] }}</div>
            </div>
          </div>

          <div class="status-card">
            <div class="status-card__icon"><svg class="icon"><use href="#i-globe"/></svg></div>
            <div>
              <div class="status-card__label">Entorno</div>
              <div class="status-card__value">{{ ucfirst($runtime['env']) }}</div>
            </div>
          </div>

          <div class="status-card">
            <div class="status-card__icon"><svg class="icon"><use href="#i-shield"/></svg></div>
            <div>
              <div class="status-card__label">Limitación</div>
              <div class="status-card__value">120 req/min</div>
            </div>
          </div>
        </div>

        <div class="stats-strip" data-reveal="4">
          <div class="stat-chip" data-color="tide">
            <svg class="icon stat-chip__icon"><use href="#i-layers"/></svg>
            <div>
              <div class="stat-chip__num">{{ $stats['categories']['count'] }}</div>
              <div class="stat-chip__name">Categorías</div>
            </div>
          </div>
          <div class="stat-chip" data-color="blue">
            <svg class="icon stat-chip__icon"><use href="#i-boat"/></svg>
            <div>
              <div class="stat-chip__num">{{ $stats['tours']['count'] }}</div>
              <div class="stat-chip__name">Tours</div>
            </div>
          </div>
          <div class="stat-chip" data-color="coral">
            <svg class="icon stat-chip__icon"><use href="#i-restaurant"/></svg>
            <div>
              <div class="stat-chip__num">{{ $stats['restaurants']['count'] }}</div>
              <div class="stat-chip__name">Restaurantes</div>
            </div>
          </div>
          <div class="stat-chip" data-color="green">
            <svg class="icon stat-chip__icon"><use href="#i-home"/></svg>
            <div>
              <div class="stat-chip__num">{{ $stats['inns']['count'] }}</div>
              <div class="stat-chip__name">Posadas nativas</div>
            </div>
          </div>
          <div class="stat-chip" data-color="gold">
            <svg class="icon stat-chip__icon"><use href="#i-cafe"/></svg>
            <div>
              <div class="stat-chip__num">{{ $stats['bakeries']['count'] }}</div>
              <div class="stat-chip__name">Panaderías</div>
            </div>
          </div>
          <div class="stat-chip" data-color="lagoon">
            <svg class="icon stat-chip__icon"><use href="#i-gift"/></svg>
            <div>
              <div class="stat-chip__num">{{ $stats['promotions']['count'] }}</div>
              <div class="stat-chip__name">Promociones</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       RECURSOS / ENDPOINTS
       ============================================================ -->
  <main class="section section--start" id="recursos">
    <div class="container">
      <div class="section__head" data-reveal>
        <span class="section__eyebrow">
          <svg class="icon icon--sm"><use href="#i-server"/></svg>
          Catálogo de endpoints
        </span>
        <h2 class="section__title">Recursos públicos de la API</h2>
        <p class="section__sub">
          Base {{ $api['root'] }}. Todos los listados soportan paginación y
          filtros por categoría; la respuesta siempre usa el mismo envelope.
        </p>
      </div>

      <div class="resources-grid">
        <article class="resource-card" data-color="tide" data-reveal>
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-layers"/></svg></span>
            <span class="resource-card__count">{{ $stats['categories']['count'] }} registros</span>
          </div>
          <div>
            <h3 class="resource-card__name">Categorías</h3>
            <p class="resource-card__desc">Agrupa tours, restaurantes y experiencias.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/categories</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="blue" data-reveal="1">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-boat"/></svg></span>
            <span class="resource-card__count">{{ $stats['tours']['count'] }} aprobados</span>
          </div>
          <div>
            <h3 class="resource-card__name">Tours</h3>
            <p class="resource-card__desc">Rutas y navegaciones por la isla, verificadas.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/tours</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="coral" data-reveal="2">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-restaurant"/></svg></span>
            <span class="resource-card__count">{{ $stats['restaurants']['count'] }} aprobados</span>
          </div>
          <div>
            <h3 class="resource-card__name">Restaurantes</h3>
            <p class="resource-card__desc">Cocina sanandresana, carta y ubicación.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/restaurants</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="green" data-reveal>
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-home"/></svg></span>
            <span class="resource-card__count">{{ $stats['inns']['count'] }} disponibles</span>
          </div>
          <div>
            <h3 class="resource-card__name">Posadas nativas</h3>
            <p class="resource-card__desc">Hospedaje local con cabida y tarifas.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/inns</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="gold" data-reveal="1">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-cafe"/></svg></span>
            <span class="resource-card__count">{{ $stats['bakeries']['count'] }} activas</span>
          </div>
          <div>
            <h3 class="resource-card__name">Panaderías</h3>
            <p class="resource-card__desc">Pan y café, con productos destacados.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/bakeries</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="blue" data-reveal="2">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-storefront"/></svg></span>
            <span class="resource-card__count">{{ $stats['businesses']['count'] }} aprobados</span>
          </div>
          <div>
            <h3 class="resource-card__name">Negocios</h3>
            <p class="resource-card__desc">Tiendas y servicios con disponibilidad.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/businesses</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="green" data-reveal>
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-bed"/></svg></span>
            <span class="resource-card__count">{{ $stats['accommodations']['count'] }} en vivo</span>
          </div>
          <div>
            <h3 class="resource-card__name">Alojamientos</h3>
            <p class="resource-card__desc">Suites y villas con disponibilidad en vivo.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/accommodations</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="lagoon" data-reveal="1">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-sparkles"/></svg></span>
            <span class="resource-card__count">{{ $stats['experiences']['count'] }} activas</span>
          </div>
          <div>
            <h3 class="resource-card__name">Experiencias</h3>
            <p class="resource-card__desc">Actividades locales curadas por SEVEN.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/experiences</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="lagoon" data-reveal="2">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-image"/></svg></span>
            <span class="resource-card__count">{{ $stats['banners']['count'] }} creativos</span>
          </div>
          <div>
            <h3 class="resource-card__name">Banners</h3>
            <p class="resource-card__desc">Creatividades activas para el home de la app.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/banners</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card" data-color="coral" data-reveal>
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-gift"/></svg></span>
            <span class="resource-card__count">{{ $stats['promotions']['count'] }} vigentes</span>
          </div>
          <div>
            <h3 class="resource-card__name">Promociones</h3>
            <p class="resource-card__desc">Ofertas válidas con vigencia automática.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">GET</span>
            <span class="resource-card__path">/api/v1/promotions</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>

        <article class="resource-card resource-card--booking" data-color="blue" data-reveal="1">
          <div class="resource-card__top">
            <span class="resource-card__icon"><svg class="icon"><use href="#i-lock"/></svg></span>
            <span class="resource-card__count">{{ $stats['bookings']['count'] }} registradas</span>
          </div>
          <div>
            <h3 class="resource-card__name">Reservas</h3>
            <p class="resource-card__desc">Crear, consultar y cancelar reservas por código.</p>
          </div>
          <div class="resource-card__route">
            <span class="resource-card__method">POST</span>
            <span class="resource-card__path">/api/v1/bookings</span>
            <a class="resource-card__go" href="{{ $api['docs_ui'] }}" aria-label="Ver en Swagger"><svg class="icon icon--sm"><use href="#i-arrow"/></svg></a>
          </div>
        </article>
      </div>
    </div>
  </main>

  <!-- ============================================================
       ENVELOPE DE RESPUESTA
       ============================================================ -->
  <section class="section">
    <div class="container">
      <div class="envelope">
        <div data-reveal>
          <div class="section__head">
            <span class="section__eyebrow">
              <svg class="icon icon--sm"><use href="#i-check"/></svg>
              Contrato de error y éxito
            </span>
            <h2 class="section__title">Respuestas consistentes</h2>
            <p class="section__sub">
              Toda respuesta viaja en un envelope único y predecible, igual que
              en la app móvil:
            </p>
          </div>

          <div class="feature-list">
            <div class="feature">
              <div class="feature__icon"><svg class="icon"><use href="#i-check"/></svg></div>
              <div>
                <div class="feature__title">success</div>
                <div class="feature__text">booleano: <code>true</code> cuando la petición terminó bien.</div>
              </div>
            </div>
            <div class="feature">
              <div class="feature__icon"><svg class="icon"><use href="#i-doc"/></svg></div>
              <div>
                <div class="feature__title">message</div>
                <div class="feature__text">Texto breve, legible y localizable del resultado.</div>
              </div>
            </div>
            <div class="feature">
              <div class="feature__icon"><svg class="icon"><use href="#i-layers"/></svg></div>
              <div>
                <div class="feature__title">data y meta</div>
                <div class="feature__text">Payload (o lista paginada) y metadatos de paginación.</div>
              </div>
            </div>
            <div class="feature">
              <div class="feature__icon"><svg class="icon"><use href="#i-shield"/></svg></div>
              <div>
                <div class="feature__title">errors</div>
                <div class="feature__text">Detalle estructurado por campo en validaciones fallidas.</div>
              </div>
            </div>
          </div>

          <div class="codes">
            <span class="code-pill"><b>200</b> OK</span>
            <span class="code-pill"><b>201</b> Created</span>
            <span class="code-pill"><b>404</b> Not Found</span>
            <span class="code-pill"><b>422</b> Validation</span>
            <span class="code-pill"><b>429</b> Rate limit</span>
          </div>
        </div>

        <div class="code-block" data-reveal="1">
          <div class="code-block__head">
            <span>lista paginada &mdash; GET /tours?per_page=2</span>
            <span class="code-block__tag">200 OK</span>
          </div>
          <pre class="code"><div class="code-row"><span class="ln">1</span><span><span class="punct">{</span></span></div><div class="code-row"><span class="ln">2</span><span>&nbsp;&nbsp;<span class="key">"success"</span><span class="punct">:</span> <span class="bool">true</span><span class="punct">,</span></span></div><div class="code-row"><span class="ln">3</span><span>&nbsp;&nbsp;<span class="key">"message"</span><span class="punct">:</span> <span class="str">"Tours retrieved successfully."</span><span class="punct">,</span></span></div><div class="code-row"><span class="ln">4</span><span>&nbsp;&nbsp;<span class="key">"data"</span><span class="punct">:</span> <span class="punct">[</span></span></div><div class="code-row"><span class="ln">5</span><span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="punct">{</span> <span class="key">"id"</span><span class="punct">:</span> <span class="bool">1</span><span class="punct">,</span> <span class="key">"name"</span><span class="punct">:</span> <span class="str">"Tour de la Isla"</span> <span class="punct">}</span><span class="punct">,</span></span></div><div class="code-row"><span class="ln">6</span><span>&nbsp;&nbsp;<span class="punct">],</span></span></div><div class="code-row"><span class="ln">7</span><span>&nbsp;&nbsp;<span class="key">"errors"</span><span class="punct">:</span> <span class="bool">null</span><span class="punct">,</span></span></div><div class="code-row"><span class="ln">8</span><span>&nbsp;&nbsp;<span class="key">"meta"</span><span class="punct">:</span> <span class="punct">{</span></span></div><div class="code-row"><span class="ln">9</span><span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="key">"current_page"</span><span class="punct">:</span> <span class="bool">1</span><span class="punct">,</span></span></div><div class="code-row"><span class="ln">10</span><span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="key">"total"</span><span class="punct">:</span> <span class="bool">24</span><span class="punct">,</span></span></div><div class="code-row"><span class="ln">11</span><span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="key">"per_page"</span><span class="punct">:</span> <span class="bool">2</span></span></div><div class="code-row"><span class="ln">12</span><span>&nbsp;&nbsp;<span class="punct">}</span></span></div><div class="code-row"><span class="ln">13</span><span><span class="punct">}</span></span></div></pre>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       BANNER SWAGGER
       ============================================================ -->
  <section class="section">
    <div class="container">
      <div class="docs-banner" data-reveal>
        <div class="docs-banner__grid">
          <div>
            <div class="docs-banner__icon">
              <svg class="icon"><use href="#i-book"/></svg>
            </div>
            <h2 class="docs-banner__title">Documentación interactiva</h2>
            <p class="docs-banner__text">
              Probá cada endpoint, parámetro y esquema en tu navegador sin
              escribir una sola línea de código.
            </p>
            <div class="docs-banner__links">
              <a class="btn btn--accent" href="{{ $api['docs_ui'] }}">
                Abrir Swagger UI
              </a>
              <a class="btn btn--inverse" href="{{ $api['docs_json'] }}">
                <svg class="icon icon--sm"><use href="#i-doc"/></svg>
                api-docs.json
              </a>
            </div>
            <div class="docs-banner__meta">
              <svg class="icon icon--sm"><use href="#i-terminal"/></svg>
              Generado con swagger-php &middot; OpenAPI 3
            </div>
          </div>

          <div>
            <div class="terminal">
              <div class="terminal__bar">
                <span class="terminal__dot terminal__dot--a"></span>
                <span class="terminal__dot terminal__dot--b"></span>
                <span class="terminal__dot terminal__dot--c"></span>
                <span class="terminal__title">$ curl {{ $api['root'] }}</span>
              </div>
              <div class="terminal__body">
                <div><span class="prompt">$</span> curl {{ $api['root'] }}</div>
                <div><br></div>
                <div><span class="punct">{</span></div>
                <div>&nbsp;&nbsp;<span class="key">"name"</span><span class="punct">:</span> <span class="str">"SEVEN API"</span><span class="punct">,</span></div>
                <div>&nbsp;&nbsp;<span class="key">"version"</span><span class="punct">:</span> <span class="str">"v1"</span><span class="punct">,</span></div>
                <div>&nbsp;&nbsp;<span class="key">"status"</span><span class="punct">:</span> <span class="str">"ok"</span></div>
                <div><span class="punct">}</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       FOOTER
       ============================================================ -->
  <footer class="footer">
    <div class="container">
      <div class="footer__inner">
        <div>
          <div class="footer__brand">
            <img class="footer__logo" src="{{ asset('images/brand/seven-logo-blanco.png') }}" alt="SEVEN">
          </div>
          <p class="footer__tagline">
            Más que un destino &mdash; la plataforma que conecta a San Andrés
            con el mundo.
          </p>
          <ul class="footer__links">
            <li><a href="{{ $api['docs_ui'] }}">Documentación</a></li>
            <li><a href="{{ $api['docs_json'] }}">api-docs.json</a></li>
            <li><a href="{{ $api['root'] }}">Base URL</a></li>
          </ul>
        </div>

        <div>
          <div class="footer__stack">
            <span class="stack-chip"><svg class="icon icon--sm"><use href="#i-server"/></svg>{{ $runtime['framework'] }}</span>
            <span class="stack-chip"><svg class="icon icon--sm"><use href="#i-code"/></svg>{{ $runtime['php'] }}</span>
            <span class="stack-chip"><svg class="icon icon--sm"><use href="#i-shield"/></svg>Sanctum</span>
            <span class="stack-chip"><svg class="icon icon--sm"><use href="#i-wifi"/></svg>REST &middot; {{ $api['version'] }}</span>
          </div>
        </div>

        <div>
          <p class="footer__tagline" style="margin-bottom:14px">
            Todos los endpoints son públicos y aptos para la app móvil.
            Limitación: <b>120 peticiones/min</b> por cliente.
          </p>
          <div class="codes">
            <span class="code-pill"><b>JSON</b> envelope</span>
            <span class="code-pill"><b>REST</b> versionado</span>
          </div>
        </div>
      </div>

      <div class="footer__bottom">
        <span>&copy; {{ now()->year }} SEVEN &mdash; San Andrés y Providencia, Colombia.</span>
        <span class="footer__madeby">
          Hecho con <span class="heart">&#9829;</span> para la isla
        </span>
      </div>
    </div>
  </footer>

</body>
</html>