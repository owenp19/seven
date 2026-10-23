<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16"/>
    <meta name="theme-color" content="#0B2D4A">
    <meta name="description" content="Documentación oficial de la API pública de SEVEN — San Andrés, más que un destino.">

    <!-- SEVEN Typography System (Oswald -> display / Poppins -> body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Ionicons (iconos globales del proyecto) -->
    <script type="module" src="https://unpkg.com/ionicons@7.4.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.4.0/dist/ionicons/ionicons.js"></script>

    <style>
    html
    {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }
    *,
    *:before,
    *:after
    {
        box-sizing: inherit;
    }

    :root {
        --seven-primary: #0077B6;
        --seven-navy: #0B2D4A;
        --seven-tidemint: #00D1C5;
        --seven-light: #E6F4FB;
        --seven-gradient: linear-gradient(160deg, #0077B6 0%, #0B2D4A 100%);
        --seven-font-display: 'Oswald', sans-serif;
        --seven-font-body: 'Poppins', sans-serif;
    }

    body {
        margin: 0;
        background: #F4FAFD;
        font-family: var(--seven-font-body);
    }

    /* ------------------------------------------------------------------
       SEVEN Preloader (sistema de precarga)
    ------------------------------------------------------------------- */
    #seven-loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--seven-gradient);
        transition: opacity .5s ease, visibility .5s ease;
    }
    #seven-loader.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .seven-loader__card {
        position: relative;
        width: 180px;
        height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 999px;
        box-shadow: 0 20px 45px rgba(4, 20, 40, 0.45);
        backdrop-filter: blur(4px);
    }
    .seven-loader__spinner {
        position: absolute;
        inset: -16px;
        border-radius: 999px;
        border: 3px solid rgba(255, 255, 255, 0.18);
        border-top-color: var(--seven-tidemint);
        animation: seven-spin 1.1s linear infinite;
    }
    .seven-loader__icon {
        font-size: 44px;
        color: #FFFFFF;
    }
    .seven-loader__brand {
        font-family: var(--seven-font-display);
        font-weight: 700;
        font-size: 26px;
        letter-spacing: 5px;
        color: #FFFFFF;
        text-transform: uppercase;
    }
    .seven-loader__tag {
        font-family: var(--seven-font-body);
        font-weight: 400;
        font-size: 11px;
        letter-spacing: 2px;
        color: rgba(255, 255, 255, 0.85);
        text-transform: uppercase;
    }
    @keyframes seven-spin {
        to { transform: rotate(360deg); }
    }

    /* ------------------------------------------------------------------
       Tipografía del proyecto
    ------------------------------------------------------------------- */
    .swagger-ui {
        font-family: var(--seven-font-body) !important;
    }
    .swagger-ui h1,
    .swagger-ui h2,
    .swagger-ui h3,
    .swagger-ui h4,
    .swagger-ui .model-title,
    .swagger-ui .opblock-tag {
        font-family: var(--seven-font-display) !important;
        font-weight: 600;
        letter-spacing: 0.4px;
        color: var(--seven-navy);
    }
    .swagger-ui .information-container h1 {
        color: var(--seven-navy);
    }
    .swagger-ui .information-container p {
        color: #33475B;
    }

    /* ------------------------------------------------------------------
       Topbar marca SEVEN
    ------------------------------------------------------------------- */
    .swagger-ui .topbar {
        background: var(--seven-gradient);
        padding: 14px 0;
        box-shadow: 0 4px 18px rgba(4, 20, 40, 0.25);
    }
    .swagger-ui .topbar .topbar-wrapper {
        align-items: center;
    }
    .swagger-ui .topbar .topbar-wrapper a {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #FFFFFF;
        font-family: var(--seven-font-display);
        font-weight: 700;
        font-size: 22px;
        letter-spacing: 4px;
        text-decoration: none;
        text-transform: uppercase;
    }
    .swagger-ui .topbar .topbar-wrapper a:before {
        content: '';
        display: inline-block;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    .swagger-ui .topbar .topbar-wrapper a:after {
        content: '· Más que un destino';
        font-family: var(--seven-font-body);
        font-weight: 400;
        font-size: 12px;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.85);
    }
    .swagger-ui .topbar .topbar-wrapper img,
    .swagger-ui .topbar .download-url-wrapper .select-label span {
        display: none;
    }
    .swagger-ui .topbar .download-url-wrapper {
        flex: 1;
    }
    .swagger-ui .download-url-input {
        border-radius: 20px !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
    }
    .swagger-ui .download-url-button {
        border-radius: 20px !important;
        background: var(--seven-tidemint) !important;
        color: var(--seven-navy) !important;
        font-weight: 600;
    }

    /* ------------------------------------------------------------------
       Contenedor de esquema / detalles
    ------------------------------------------------------------------- */
    .swagger-ui .scheme-container {
        background: #FFFFFF;
        border-radius: 12px;
        margin: 22px 0;
        box-shadow: 0 6px 24px rgba(0, 119, 182, 0.10);
        border: 1px solid rgba(0, 119, 182, 0.18);
    }
    .swagger-ui .scheme-container .servers-title,
    .swagger-ui .scheme-container .servers {
        font-family: var(--seven-font-body);
        color: var(--seven-navy);
    }

    /* ------------------------------------------------------------------
       Botones / controles con color primario SEVEN
    ------------------------------------------------------------------- */
    .swagger-ui .btn {
        font-family: var(--seven-font-body) !important;
    }
    .swagger-ui .btn.authorize {
        color: var(--seven-primary);
        border-color: var(--seven-primary);
        border-radius: 20px;
    }
    .swagger-ui .btn.authorize svg {
        fill: var(--seven-primary);
    }
    .swagger-ui .btn.execute {
        background: var(--seven-primary) !important;
        border-color: var(--seven-primary) !important;
        border-radius: 20px;
    }
    .swagger-ui .opblock .opblock-btn button {
        color: var(--seven-primary);
    }
    .swagger-ui .opblock .opblock-summary-path {
        font-family: var(--seven-font-body) !important;
        color: var(--seven-navy) !important;
        font-weight: 500;
    }
    .swagger-ui .opblock .opblock-section-header {
        background: rgba(0, 119, 182, 0.08);
    }
    .swagger-ui .opblock.opblock-get { border-color: var(--seven-primary); background: rgba(0, 119, 182, 0.04); }
    .swagger-ui .opblock.opblock-get .opblock-summary-method { background: var(--seven-primary); }
    .swagger-ui .opblock.opblock-post .opblock-summary-method { background: #0E9F8E; }
    .swagger-ui .opblock.opblock-put .opblock-summary-method { background: #C07A23; }
    .swagger-ui .opblock.opblock-delete .opblock-summary-method { background: #C2443E; }
    .swagger-ui table.parameters td,
    .swagger-ui table.parameters tr,
    .swagger-ui .parameter__name,
    .swagger-ui .parameter__type,
    .swagger-ui .parameter__default-value,
    .swagger-ui .prop-name,
    .swagger-ui .prop-type {
        font-family: var(--seven-font-body) !important;
    }
    .swagger-ui .parameter__name,
    .swagger-ui .parameter__in {
        font-weight: 500;
    }
    .swagger-ui .parameter__in,
    .swagger-ui .parameters-col_description .markdown p {
        color: #4A5E73;
    }

    /* ------------------------------------------------------------------
       Filtro / barra inferior
    ------------------------------------------------------------------- */
    .swagger-ui .opblock-tag {
        border-bottom: 2px solid rgba(0, 119, 182, 0.25);
    }
    .swagger-ui .wrapper {
        max-width: 1460px;
    }

    /* ------------------------------------------------------------------
       Spinner de carga de Swagger UI con el color primario
    ------------------------------------------------------------------- */
    .swagger-ui .loading-container .loading:before {
        border-color: rgba(0, 119, 182, 0.15);
        border-top-color: var(--seven-primary);
    }

    /* ------------------------------------------------------------------
       Dark mode (opcional) — conserva la marca
    ------------------------------------------------------------------- */
    @if(config('l5-swagger.defaults.ui.display.dark_mode'))
        body#dark-mode { background: #0A1A2A; }
        #dark-mode .scheme-container { background: #0F2440; border-color: rgba(0, 119, 182, 0.5); }
    @endif
    </style>
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>

<div id="seven-loader" aria-hidden="true">
    <div class="seven-loader__card">
        <div class="seven-loader__spinner"></div>
        <ion-icon class="seven-loader__icon" name="boat" aria-label="SEVEN"></ion-icon>
        <span class="seven-loader__brand">SEVEN</span>
        <span class="seven-loader__tag">Más que un destino</span>
    </div>
</div>

<div id="swagger-ui"></div>

<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function() {
        const urls = [];

        @foreach($urlsToDocs as $title => $url)
            urls.push({name: "{{ $title }}", url: "{{ $url }}"});
        @endforeach

        const loader = document.getElementById('seven-loader');

        // Oculta el preloader al terminar la renderización de la interfaz.
        const hideLoader = function () {
            if (loader) {
                loader.classList.add('is-hidden');
            }
        };
        // Respaldo: si la interfaz no llega a completarse, no dejar el preloader eterno.
        setTimeout(hideLoader, 20000);

        // Build a system
        const ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            urls: urls,
            "urls.primaryName": "{{ $documentationTitle }}",
            operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
            configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
            validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
            oauth2RedirectUrl: "{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}",

            onComplete: hideLoader,

            requestInterceptor: function(request) {
                request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                return request;
            },

            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],

            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],

            layout: "StandaloneLayout",
            docExpansion : "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
            deepLinking: true,
            filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
            persistAuthorization: "{!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}",
            displayRequestDuration: true,
            showCommonExtensions: true,

        })

        window.ui = ui

        @if(in_array('oauth2', array_column(config('l5-swagger.defaults.securityDefinitions.securitySchemes'), 'type')))
        ui.initOAuth({
            usePkceWithAuthorizationCodeGrant: "{!! (bool)config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') !!}"
        })
        @endif
    }
</script>
</body>
</html>