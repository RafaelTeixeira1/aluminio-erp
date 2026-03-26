<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acesso negado</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
        <main class="mx-auto flex min-h-screen max-w-3xl items-center justify-center px-6 py-12">
            <section class="w-full rounded-2xl bg-white p-8 shadow-lg border border-gray-200">
                <p class="text-sm font-semibold uppercase tracking-wide text-red-600">Erro 403</p>
                <h1 class="mt-2 text-3xl font-bold">Acesso negado</h1>
                <p class="mt-4 text-gray-600">
                    Seu perfil atual nao possui permissao para acessar esta area do sistema.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('dashboard.index') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 transition">
                        Voltar ao dashboard
                    </a>
                    <a href="javascript:history.back()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        Voltar para pagina anterior
                    </a>
                </div>
            </section>
        </main>
    </body>
</html>
