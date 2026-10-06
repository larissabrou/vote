<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Système de Vote')</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $hasViteBuild = file_exists(public_path('build/manifest.json'));
        $hasFallbackCss = file_exists(public_path('css/app.css'));
    @endphp
    @if($hasViteBuild)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @elseif($hasFallbackCss)
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @if(file_exists(public_path('js/app.js')))
            <script src="{{ asset('js/app.js') }}" defer></script>
        @endif
    @else
        {{-- Pas de build : exécuter "npm run build" en local puis déployer public/build/ --}}
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @if(file_exists(public_path('js/app.js')))
            <script src="{{ asset('js/app.js') }}" defer></script>
        @endif
    @endif
    @stack('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-[#ffe7c2] via-white to-[#ffe7c2] min-h-screen font-sans overflow-x-hidden">
    <nav class="bg-white/80 backdrop-blur-lg border-b border-gray-200/50 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-14 sm:h-16 lg:h-20 min-h-[3.5rem]">
                <a href="{{ route('accueil') }}" class="flex items-center gap-2 sm:gap-3 group min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 flex-shrink-0 bg-transparent p-0.5">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="max-w-full max-h-full w-auto h-auto object-contain" style="max-width: 100%; max-height: 100%;">
                    </div>
                    <div class="min-w-0 overflow-hidden">
                        <div class="text-xs sm:text-base md:text-xl font-bold bg-gradient-to-r from-primary-500 to-[#93c46a] bg-clip-text text-transparent truncate">
                            Vote System
                        </div>
                        <div class="text-xs text-gray-500 font-medium hidden sm:block">Plateforme Électorale</div>
                    </div>
                </a>
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                    @auth
                        <a href="{{ route('elections.liste') }}" class="px-3 py-2.5 sm:px-6 sm:py-2.5 rounded-lg font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0" aria-label="Mes élections">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden sm:inline">Mes élections</span>
                        </a>
                        @php
                            $displayName = Auth::user()->name
                                ? \Illuminate\Support\Str::before(trim(Auth::user()->name), ' ') ?: trim(Auth::user()->name)
                                : Auth::user()->email;
                        @endphp
                        <span class="hidden md:inline text-sm text-gray-600 truncate max-w-[120px]">{{ $displayName }}</span>
                        <form action="{{ route('administration.deconnexion') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2.5 sm:px-6 sm:py-2.5 rounded-lg font-medium text-red-600 hover:bg-red-50 transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0" aria-label="Déconnexion">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="hidden sm:inline">Déconnexion</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('administration.connexion') }}" class="px-3 py-2.5 sm:px-6 sm:py-2.5 rounded-lg font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0" aria-label="Connexion">
                            <svg class="w-5 h-5 flex-shrink-0 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            <span class="hidden sm:inline">Connexion</span>
                        </a>
                        <a href="{{ route('administration.inscription') }}" class="px-3 py-2.5 sm:px-4 sm:py-2.5 rounded-lg font-medium text-white bg-primary-500 hover:bg-primary-600 transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0" aria-label="Créer un compte">
                            <svg class="w-5 h-5 flex-shrink-0 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            <span class="hidden sm:inline">Créer un compte</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="min-h-[calc(100vh-3.5rem)] sm:min-h-[calc(100vh-4rem)] lg:min-h-[calc(100vh-5rem)] py-4 sm:py-6 lg:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 w-full max-w-[100vw]">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            window.showAppPopup = function(type, message, title) {
                if (typeof Swal === 'undefined') {
                    return;
                }
                var icon = ['success', 'error', 'warning', 'info', 'question'].includes(type) ? type : 'info';
                Swal.fire({
                    icon: icon,
                    title: title || 'Information',
                    text: message || '',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#16a34a',
                });
            };

            window.showAppConfirm = function(message, onConfirm) {
                if (typeof Swal === 'undefined') {
                    if (confirm(message)) {
                        onConfirm();
                    }
                    return;
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Confirmation',
                    text: message || 'Confirmer cette action ?',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, confirmer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        onConfirm();
                    }
                });
            };

            document.addEventListener('submit', function(e) {
                var form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                var message = form.getAttribute('data-confirm-message');
                if (!message || form.dataset.confirmHandled === '1') return;
                e.preventDefault();
                window.showAppConfirm(message, function() {
                    form.dataset.confirmHandled = '1';
                    form.submit();
                });
            });

            @if(session('success'))
                window.showAppPopup('success', @json(session('success')), 'Succès');
            @endif
            @if(session('info'))
                window.showAppPopup('info', @json(session('info')), 'Information');
            @endif
            @if(session('warning'))
                window.showAppPopup('warning', @json(session('warning')), 'Attention');
            @endif
            @if(session('error'))
                window.showAppPopup('error', @json(session('error')), 'Erreur');
            @endif
            @if($errors->any())
                window.showAppPopup('error', @json(implode("\n", $errors->all())), 'Erreurs de validation');
            @endif

            var votersInput = document.getElementById('voters_file');
            if (!votersInput) return;
            var formGroup = votersInput.closest('.form-group');
            if (!formGroup) return;
            if (formGroup.querySelector('a[href*="modele-votants"]')) return;
            var small = formGroup.querySelector('small');
            if (!small) return;
            var a = document.createElement('a');
            a.href = '/elections/modele-votants';
            a.className = 'inline-flex items-center justify-center w-9 h-9 rounded-lg text-primary-600 hover:bg-primary-50 border-2 border-primary-500 hover:border-primary-600 flex-shrink-0';
            a.title = 'Télécharger le modèle Excel (.xlsx)';
            a.setAttribute('aria-label', 'Télécharger le modèle Excel');
            a.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>';
            var wrap = document.createElement('div');
            wrap.className = 'flex items-center gap-2 mt-1 flex-wrap';
            small.parentNode.insertBefore(wrap, small);
            wrap.appendChild(small);
            wrap.appendChild(a);
        });
    })();
    </script>
</body>
</html>

