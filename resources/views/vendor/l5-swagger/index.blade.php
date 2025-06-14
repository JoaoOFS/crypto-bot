<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ config('l5-swagger.documentations.'.$documentation.'.api.title') }}</title>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32" />
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16" />
    <link href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <!-- Loading animation -->
    <style>
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>

    <div id="swagger-ui"></div>

    <script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
    <script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
    <script>
        window.onload = function() {
            // Build a system
            const ui = SwaggerUIBundle({
                dom_id: '#swagger-ui',
                url: "{!! $urlToDocs !!}",
                operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
                configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
                validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
                oauth2RedirectUrl: "{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}",
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
                docExpansion: "{{ config('l5-swagger.defaults.ui.display.doc_expansion', 'none') }}",
                deepLinking: true,
                showExtensions: true,
                showCommonExtensions: true,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                defaultModelRendering: 'model',
                displayRequestDuration: true,
                filter: true,
                persistAuthorization: "{{ config('l5-swagger.defaults.ui.authorization.persist_authorization', false) }}"
            });

            window.ui = ui;

            // Corrigido: initOAuth não retorna Promise
            ui.initOAuth({
                usePkceWithAuthorizationCodeGrant: true
            });
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>
