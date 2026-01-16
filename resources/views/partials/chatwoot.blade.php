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
                });
            }
        })(document, "script");

        // Global listener for "open-chat" class or href="#chat"
        document.addEventListener('click', function(e) {
            // Procura pro link mais próximo (necessário se tiver ícone dentro do a)
            var target = e.target.closest('a') || e.target;
            
            // Verifica se tem a classe open-chat
            if (target.classList && target.classList.contains('open-chat')) {
                 e.preventDefault();
                 window.$chatwoot.toggle('open');
                 return;
            }

            // Verifica se o href termina com #chat
            if (target.getAttribute && target.getAttribute('href') && target.getAttribute('href').endsWith('#chat')) {
                 e.preventDefault();
                 window.$chatwoot.toggle('open');
            }
        });
    </script>
@endif