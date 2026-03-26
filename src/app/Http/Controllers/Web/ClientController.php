<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::query()
            ->withCount('sales')
            ->withSum('sales as total_spent', 'total')
            ->orderBy('name')
            ->paginate(15);

        return view('clients.index', ['clients' => $clients]);
    }

    public function create(): View
    {
        return view('clients.form', ['client' => null]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Cliente criado com sucesso!');
    }

    public function edit(Client $client): View
    {
        return view('clients.form', ['client' => $client]);
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente removido com sucesso!');
    }
}