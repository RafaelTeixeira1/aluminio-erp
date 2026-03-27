<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login - SD Alumínios</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="text-center mb-8">
                    @php
                        $hasPngLogo = is_file(public_path('images/company-logo.png'));
                        $logoVersion = $hasPngLogo ? filemtime(public_path('images/company-logo.png')) : time();
                    @endphp
                    @if($hasPngLogo)
                        <img src="{{ asset('images/company-logo.png') }}?v={{ $logoVersion }}" alt="Logo da empresa" class="mx-auto h-20 w-auto max-w-[220px] object-contain">
                    @else
                        <img src="{{ asset('images/company-logo.svg') }}" alt="Logo da empresa" class="mx-auto h-20 w-auto max-w-[220px] object-contain">
                    @endif
                </div>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('auth.login') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="seu@email.com.br">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="••••••••">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                        Entrar
                    </button>
                </form>

                <div class="mt-6 text-center text-xs text-gray-500">
                    Dev Rafael Teixeira
                </div>
            </div>
        </div>
    </body>
</html>
