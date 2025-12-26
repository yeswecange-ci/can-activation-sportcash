<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - CAN 2025 LONACI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 3rem 2.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 400;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-icon {
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            transition: all 0.2s;
            font-family: inherit;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-checkbox input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
            accent-color: #3b82f6;
        }

        .form-checkbox label {
            font-size: 0.875rem;
            color: #4b5563;
            cursor: pointer;
            user-select: none;
        }

        .btn {
            width: 100%;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            background: #3b82f6;
            color: white;
        }

        .btn:hover {
            background: #2563eb;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .footer p {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }

            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <h1 class="login-title">CAN 2025 LONACI</h1>
                <p class="login-subtitle">Connexion Backoffice</p>
            </div>

            <!-- Alert -->
            <div class="alert" style="display: none;" id="errorAlert">
                <span class="alert-icon">⚠</span>
                <span id="errorMessage"></span>
            </div>

            <!-- Form -->
            <form method="POST" action="/admin/login">
                @csrf
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="form-input"
                        placeholder="admin@can2025.cd"
                        required
                        autofocus
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-input"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <!-- Remember Me -->
                <div class="form-checkbox">
                    <input id="remember" type="checkbox" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn">
                    Se connecter
                </button>
            </form>

            <!-- Footer -->
            <div class="footer">
                <p>© 2024 CAN 2025 Kinshasa. Tous droits réservés.</p>
            </div>
        </div>
    </div>

    <script>
        // Animation légère sur focus des inputs
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                const label = this.parentElement.querySelector('.form-label');
                if (label) {
                    label.style.color = '#3b82f6';
                }
            });

            input.addEventListener('blur', function() {
                const label = this.parentElement.querySelector('.form-label');
                if (label) {
                    label.style.color = '#374151';
                }
            });
        });
    </script>
</body>
</html>
