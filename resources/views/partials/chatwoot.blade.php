@php
    $config = \App\Models\Configuration::where('chave', 'chatwoot')->first();
    $chatwoot = $config ? json_decode($config->valor, true) : null;
@endphp

@if($chatwoot && ($chatwoot['enabled'] ?? false))
    <script>
        (function (d, t) {
            var BASE_URL = "{{ rtrim($chatwoot['base_url'], '/') }}";
            var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
            g.src = BASE_URL + "/packs/js/sdk.js";
            g.defer = true;
            g.async = true;
            s.parentNode.insertBefore(g, s);
            g.onload = function () {
                window.chatwootSDK.run({
                    websiteToken: "{{ $chatwoot['website_token'] }}",
                    baseUrl: BASE_URL
                })
            }
        })(document, "script");
    </script>
@endif