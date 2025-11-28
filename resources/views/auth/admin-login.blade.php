<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - CAN 2025</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-red-50 via-white to-gray-50 h-screen overflow-hidden m-0 p-0">
    <div class="h-screen flex items-center justify-center px-4 py-6">
        <div class="w-full max-w-6xl h-full max-h-[800px] flex flex-col lg:flex-row items-center justify-center gap-6 lg:gap-12">

            <!-- Section Logo et Présentation -->
            <div class="w-full lg:w-1/2 flex flex-col items-center lg:items-start text-center lg:text-left space-y-4 lg:space-y-6">
                <div class="mb-2 lg:mb-4 transition-transform duration-300 hover:-translate-y-2">
                    <img src="{{ url('/images/logobra.png') }}" alt="Bracongo Logo" class="w-32 h-32 lg:w-40 lg:h-40 object-contain">
                </div>

                <!-- Titre et description -->
                <h1 class="text-2xl lg:text-3xl xl:text-4xl font-bold text-gray-900 leading-tight">
                    Bienvenue sur <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-black">CAN 2025 Kinshasa</span>
                </h1>
                <p class="text-base lg:text-lg text-gray-600 max-w-md leading-relaxed">
                    Plateforme de gestion et d'administration de l'activation CAN 2025
                </p>

                <!-- Points forts -->
                <div class="mt-4 lg:mt-6 space-y-3 lg:space-y-4 hidden lg:block">
                    <div class="flex items-center group">
                        <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-red-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-red-600 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm lg:text-base font-medium">Gestion des activations en temps réel</span>
                    </div>
                    <div class="flex items-center group">
                        <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-red-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-red-600 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm lg:text-base font-medium">Suivi des partenaires et villages</span>
                    </div>
                    <div class="flex items-center group">
                        <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-red-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-red-600 group-hover:text-white transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm lg:text-base font-medium">Sécurité et confidentialité garanties</span>
                    </div>
                </div>
            </div>

            <!-- Section Formulaire -->
            <div class="w-full lg:w-1/2 max-w-md">
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 lg:p-8">
                    <div class="text-center mb-6 lg:mb-8">
                        <h2 class="text-xl lg:text-2xl font-bold text-gray-900">
                            Connectez-vous
                        </h2>
                        <p class="text-sm text-gray-600 mt-2">
                            Accédez à votre espace d'administration
                        </p>
                    </div>

                    <!-- Messages d'erreur -->
                    @if ($errors->any())
                        <div class="mb-4 lg:mb-6 p-3 lg:p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-700">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm">{{ $errors->first() }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Formulaire de connexion -->
                    <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4 lg:space-y-5" id="login-form">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                Adresse email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                    </svg>
                                </div>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    class="block w-full pl-10 pr-4 py-2.5 lg:py-3 bg-gray-50 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent focus:bg-white transition-all text-gray-900 placeholder-gray-400 text-sm lg:text-base"
                                    placeholder="admin@can2025.com"
                                >
                            </div>
                        </div>

                        <!-- Mot de passe -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                Mot de passe
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    class="block w-full pl-10 pr-4 py-2.5 lg:py-3 bg-gray-50 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent focus:bg-white transition-all text-gray-900 placeholder-gray-400 text-sm lg:text-base"
                                    placeholder="••••••••"
                                >
                            </div>
                        </div>

                        <!-- Se souvenir de moi -->
                        <div class="flex items-center pt-1">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                class="w-4 h-4 text-red-600 bg-gray-50 border-2 border-gray-300 rounded focus:ring-2 focus:ring-red-500 transition-all cursor-pointer"
                            >
                            <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                Se souvenir de moi
                            </label>
                        </div>

                        <!-- Bouton de connexion -->
                        <button
                            type="submit"
                            id="login-btn"
                            class="w-full flex items-center justify-center px-6 py-3 lg:py-3.5 bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold rounded-lg hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-4 focus:ring-red-500/50 transition-all shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 transform hover:-translate-y-0.5 text-sm lg:text-base disabled:opacity-75 disabled:cursor-not-allowed"
                        >
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span id="btn-text">Se connecter</span>
                            <svg id="loader" class="hidden ml-2 w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4 lg:mt-6">
                    <p class="text-xs text-gray-500">
                        🔒 Accès réservé au personnel autorisé uniquement
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        © 2025 CAN Kinshasa - Tous droits réservés
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            const loginBtn = document.getElementById('login-btn');
            const btnText = document.getElementById('btn-text');
            const loader = document.getElementById('loader');

            if (loginForm && loginBtn && btnText && loader) {
                loginForm.addEventListener('submit', function(e) {
                    loader.classList.remove('hidden');
                    btnText.textContent = 'Connexion...';
                    loginBtn.disabled = true;
                });
            }
        });
    </script>
</body>
</html>
