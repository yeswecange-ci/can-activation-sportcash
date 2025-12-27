<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - CAN 2025 Kinshasa</title>
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

        <!-- Carte de Réinitialisation -->
        <div class="bg-white rounded-xl shadow-md p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6 text-center">Nouveau mot de passe</h2>

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
            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-green-500 focus:border-can-green-500 transition-colors"
                        placeholder="votre@email.com"
                    >
                </div>

                <!-- Nouveau mot de passe -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Nouveau mot de passe
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-green-500 focus:border-can-green-500 transition-colors"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Confirmer le mot de passe -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirmer le mot de passe
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-can-green-500 focus:border-can-green-500 transition-colors"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Bouton de réinitialisation -->
                <button
                    type="submit"
                    class="w-full bg-can-green-600 hover:bg-can-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-can-green-500 focus:ring-offset-2"
                >
                    Réinitialiser le mot de passe
                </button>
            </form>
        </div>

        <!-- Lien de retour -->
        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-can-green-600 hover:underline">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</body>
</html>
