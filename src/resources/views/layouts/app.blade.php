<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SD Alumínios') }} - @yield('title')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50">
        <div id="mobile-sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-gray-900/40 md:hidden"></div>
        <div class="min-h-screen md:flex">
            <!-- Sidebar -->
            <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] -translate-x-full overflow-y-auto border-r border-gray-200 bg-white transition-transform duration-200 ease-out md:static md:z-auto md:w-64 md:max-w-none md:translate-x-0">
                <!-- Logo -->
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-6">
                    <div>
                    @php
                        $hasPngLogo = is_file(public_path('images/company-logo.png'));
                        $logoVersion = $hasPngLogo ? filemtime(public_path('images/company-logo.png')) : time();
                    @endphp
                    @if($hasPngLogo)
                        <img src="{{ asset('images/company-logo.png') }}?v={{ $logoVersion }}" alt="Logo da empresa" class="h-16 w-auto max-w-[180px] object-contain mb-2">
                    @else
                        <img src="{{ asset('images/company-logo.svg') }}" alt="Logo da empresa" class="h-16 w-auto max-w-[180px] object-contain mb-2">
                    @endif
                    <h1 class="text-2xl font-bold text-blue-600">SD Alumínios</h1>
                    <p class="text-sm text-gray-600 mt-1">Sistema de Gestão</p>
                    </div>
                    <button id="mobile-sidebar-close" type="button" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 md:hidden" aria-label="Fechar menu lateral">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Nav Menu -->
                @php
                    $profile = Auth::user()->profile ?? null;
                    $navItemClass = static fn (bool $active): string =>
                        'flex items-center px-4 py-3 rounded-lg hover:bg-blue-50 transition '.($active ? 'bg-blue-50 text-blue-600' : 'text-gray-700');
                @endphp
                <nav class="space-y-2 px-3 py-6">
                    <a href="{{ route('dashboard.index') }}" class="{{ $navItemClass(request()->routeIs('dashboard.index')) }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11v-5m0 0V9m0 5a1 1 0 11-2 0m0 0a1 1 0 012 0"></path>
                        </svg>
                        Dashboard
                    </a>

                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Operações</p>

                        @if(in_array($profile, ['admin', 'vendedor'], true))
                        <a href="/vendas" class="{{ $navItemClass(request()->is('vendas*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Vendas
                        </a>

                        <a href="/orcamentos" class="{{ $navItemClass(request()->is('orcamentos*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Orçamentos
                        </a>

                        <a href="/desenhos" class="{{ $navItemClass(request()->is('desenhos*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 14.5a2 2 0 01-.95.536L8 16l.964-3.586A2 2 0 019.5 11.5z"></path>
                            </svg>
                            Desenhos
                        </a>
                        @endif

                        @if($profile === 'admin')
                        <a href="/financeiro/receber" class="{{ $navItemClass(request()->is('financeiro/receber*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2zm7 4h.01"></path>
                            </svg>
                            Financeiro - Receber
                        </a>

                        <a href="/financeiro/pagar" class="{{ $navItemClass(request()->is('financeiro/pagar*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Financeiro - Pagar
                        </a>

                        <a href="/financeiro/fluxo-caixa" class="{{ $navItemClass(request()->is('financeiro/fluxo-caixa*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8"></path>
                            </svg>
                            Financeiro - Fluxo Caixa
                        </a>

                        <a href="/financeiro/dre" class="{{ $navItemClass(request()->is('financeiro/dre*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0a2 2 0 002 2h2a2 2 0 002-2M9 17V9a2 2 0 012-2h2a2 2 0 012 2v8m0 0V5a2 2 0 012-2h2a2 2 0 012 2v12"></path>
                            </svg>
                            Financeiro - DRE
                        </a>
                        @endif

                        @if(in_array($profile, ['admin', 'estoquista', 'vendedor'], true))
                        <a href="/estoque" class="{{ $navItemClass(request()->is('estoque*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9-4v4m0 0v4"></path>
                            </svg>
                            Estoque
                        </a>
                        @endif
                    </div>

                    @if(in_array($profile, ['admin', 'estoquista'], true))
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Compras</p>
                        
                        <a href="/fornecedores" class="{{ $navItemClass(request()->is('fornecedores*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.581m0 0H9m5.581 0a2 2 0 100-4 2 2 0 000 4zm0 0a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                            Fornecedores
                        </a>

                        <a href="/compras" class="{{ $navItemClass(request()->is('compras*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Pedidos de Compra
                        </a>
                    </div>
                    @endif

                    @if(in_array($profile, ['admin', 'vendedor'], true))
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Catálogo</p>
                        
                        <a href="/produtos" class="{{ $navItemClass(request()->is('produtos*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Produtos
                        </a>

                        @if($profile === 'admin')
                        <a href="/categorias" class="{{ $navItemClass(request()->is('categorias*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            Categorias
                        </a>
                        @endif
                    </div>
                    @endif

                    @if(in_array($profile, ['admin', 'vendedor'], true))
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">CRM</p>
                        
                        <a href="/clientes" class="{{ $navItemClass(request()->is('clientes*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2z"></path>
                            </svg>
                            Clientes
                        </a>
                    </div>
                    @endif

                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Relatórios</p>
                        
                        <a href="/relatorios" class="{{ $navItemClass(request()->is('relatorios*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Relatórios
                        </a>
                    </div>

                    @if($profile === 'admin')
                    <div class="pt-4">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Administração</p>
                        
                        <a href="/usuarios" class="{{ $navItemClass(request()->is('usuarios*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                            </svg>
                            Usuários
                        </a>

                        <a href="{{ route('settings.commercial.edit') }}" class="{{ $navItemClass(request()->is('configuracoes/comerciais*')) }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.343-4 3s1.79 3 4 3 4 1.343 4 3-1.79 3-4 3m0-12V5m0 3v10m0 0v1m0-1H9m3 0h3"></path>
                            </svg>
                            Config. Comerciais
                        </a>
                    </div>
                    @endif
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="flex min-w-0 flex-1 flex-col">
                <!-- Top Bar -->
                <header class="bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3 px-4 py-4 md:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <button id="mobile-sidebar-open" type="button" class="rounded-md border border-gray-200 p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="Abrir menu lateral">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h2 class="truncate text-base font-semibold text-gray-900 md:text-lg">@yield('page-title', 'Página')</h2>
                        </div>
                        
                        <div class="flex items-center space-x-3 md:space-x-6">
                            @php
                                $pendingNotifications = 0;
                                if (\Illuminate\Support\Facades\Schema::hasTable('quotes')) {
                                    $pendingNotifications += (int) \Illuminate\Support\Facades\DB::table('quotes')->where('status', 'aberto')->count();
                                }
                                if (\Illuminate\Support\Facades\Schema::hasTable('catalog_items')) {
                                    $pendingNotifications += (int) \Illuminate\Support\Facades\DB::table('catalog_items')->whereColumn('stock', '<=', 'stock_minimum')->count();
                                }
                            @endphp
                            <a href="{{ route('dashboard.index') }}" class="relative p-2 text-gray-600 hover:text-gray-900 transition" title="Pendências operacionais">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if($pendingNotifications > 0)
                                    <span class="absolute top-0 right-0 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">{{ $pendingNotifications > 99 ? '99+' : $pendingNotifications }}</span>
                                @endif
                            </a>

                            <details class="relative">
                                <summary class="flex cursor-pointer list-none items-center space-x-2 rounded-lg px-2 py-2 hover:bg-gray-100 transition md:px-4">
                                    <div class="w-8 h-8 bg-linear-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                                    <span class="hidden text-gray-700 sm:inline">{{ Auth::user()->name }}</span>
                                </summary>
                                
                                <div class="absolute right-0 z-50 mt-2 w-48 rounded-lg bg-white py-2 shadow-lg">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Perfil</a>
                                    @if((string) (Auth::user()->profile ?? '') === 'admin')
                                        <a href="{{ route('settings.commercial.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Configurações</a>
                                    @else
                                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Configurações</a>
                                    @endif
                                    <hr class="my-2">
                                    <form action="{{ route('logout') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="w-full text-left block px-4 py-2 text-gray-700 hover:bg-gray-100">Sair</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-x-auto p-4 md:p-6">
                    <div class="min-w-0">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <script>
            (() => {
                const body = document.body;
                const sidebar = document.getElementById('app-sidebar');
                const openBtn = document.getElementById('mobile-sidebar-open');
                const closeBtn = document.getElementById('mobile-sidebar-close');
                const backdrop = document.getElementById('mobile-sidebar-backdrop');

                if (!sidebar || !openBtn || !closeBtn || !backdrop) {
                    return;
                }

                const closeSidebar = () => {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    body.classList.remove('overflow-hidden');
                };

                const openSidebar = () => {
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.remove('hidden');
                    body.classList.add('overflow-hidden');
                };

                openBtn.addEventListener('click', openSidebar);
                closeBtn.addEventListener('click', closeSidebar);
                backdrop.addEventListener('click', closeSidebar);

                sidebar.querySelectorAll('a').forEach((anchor) => {
                    anchor.addEventListener('click', () => {
                        if (window.innerWidth < 768) {
                            closeSidebar();
                        }
                    });
                });

                window.addEventListener('resize', () => {
                    if (window.innerWidth >= 768) {
                        backdrop.classList.add('hidden');
                        body.classList.remove('overflow-hidden');
                        sidebar.classList.remove('-translate-x-full');
                    } else {
                        sidebar.classList.add('-translate-x-full');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeSidebar();
                    }
                });
            })();
        </script>

        @stack('modals')
        @yield('scripts')
    </body>
</html>
