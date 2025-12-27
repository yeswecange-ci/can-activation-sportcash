<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - CAN 2025 LONACI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo et Titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-can-gold-500 rounded-full mb-4 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">CAN 2025 LONACI</h1>
            <p class="text-gray-600">Connexion Administrateur</p>
        </div>

        <!-- Carte de Connexion -->
        <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 text-center">Accès Backoffice</h2>

            <!-- Alertes -->
            @if (session('status'))
                <div class="mb-6 px-4 py-3 rounded-lg bg-green-50 border-l-4 border-green-500 text-green-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 px-4 py-3 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-800 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Formulaire -->
            <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-gold-500 focus:border-can-gold-500 transition-colors"
                        placeholder="admin@can2025.cd"
                    >
                </div>

                <!-- Mot de passe -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Mot de passe
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-gold-500 focus:border-can-gold-500 transition-colors"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Se souvenir de moi -->
                <div class="flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="w-4 h-4 text-can-gold-600 border-gray-300 rounded focus:ring-can-gold-500"
                    >
                    <label for="remember_me" class="ml-2 text-sm text-gray-700">
                        Se souvenir de moi
                    </label>
                </div>

                <!-- Bouton de connexion -->
                <button
                    type="submit"
                    class="w-full bg-can-gold-600 hover:bg-can-gold-700 text-white font-semibold py-3 px-4 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-can-gold-500 focus:ring-offset-2"
                >
                    Se connecter
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
                    © 2024 CAN 2025 Kinshasa - Tous droits réservés
                </p>
            </div>
        </div>

        <!-- Info supplémentaire -->
        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-can-gold-600 hover:underline">
                ← Retour à la connexion utilisateur
            </a>
        </div>
    </div>
</body>
</html>
