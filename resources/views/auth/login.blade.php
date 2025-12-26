<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CAN 2025 Kinshasa</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo et Titre -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🦁</div>
            <h1 class="text-3xl font-bold text-can-black mb-2">CAN 2025</h1>
            <p class="text-gray-600">Kinshasa - Espace Pronostics</p>
        </div>

        <!-- Carte de Connexion -->
        <div class="bg-white rounded-xl shadow-md p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6 text-center">Connexion</h2>

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
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
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
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-green-500 focus:border-can-green-500 transition-colors"
                        placeholder="votre@email.com"
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
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-green-500 focus:border-can-green-500 transition-colors"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Se souvenir de moi -->
                <div class="flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="w-4 h-4 text-can-green-600 border-gray-300 rounded focus:ring-can-green-500"
                    >
                    <label for="remember_me" class="ml-2 text-sm text-gray-700">
                        Se souvenir de moi
                    </label>
                </div>

                <!-- Bouton de connexion -->
                <button
                    type="submit"
                    class="w-full bg-can-green-600 hover:bg-can-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-can-green-500 focus:ring-offset-2"
                >
                    Se connecter
                </button>

                <!-- Mot de passe oublié -->
                @if (Route::has('password.request'))
                    <div class="text-center">
                        <a href="{{ route('password.request') }}" class="text-sm text-can-green-600 hover:text-can-green-700 hover:underline">
                            Mot de passe oublié?
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Lien d'inscription -->
        @if (Route::has('register'))
            <div class="text-center mt-6">
                <p class="text-gray-600">
                    Pas encore de compte?
                    <a href="{{ route('register') }}" class="text-can-green-600 hover:text-can-green-700 font-semibold hover:underline">
                        S'inscrire
                    </a>
                </p>
            </div>
        @endif
    </div>
</body>
</html>
