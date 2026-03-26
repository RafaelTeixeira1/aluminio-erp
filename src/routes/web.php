<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\ManagementController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CatalogItemController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\CommercialSettingsController;
use App\Http\Controllers\Web\FinancialStatementController;
use App\Http\Controllers\Web\DesignSketchController;
use App\Http\Controllers\Web\QuoteController;
use App\Http\Controllers\Web\CashFlowController;
use App\Http\Controllers\Web\PayableController;
use App\Http\Controllers\Web\ReceivableController;
use App\Http\Controllers\Web\SaleController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\PurchaseOrderController;
use App\Http\Controllers\Web\SequenceController;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/perfil', [ManagementController::class, 'profile'])->name('profile.edit');
    Route::put('/perfil', [ManagementController::class, 'updateProfile'])->name('profile.update');

    Route::middleware('profile:admin,vendedor,estoquista,operador')->group(function () {
        Route::get('/dashboard', [ManagementController::class, 'dashboard'])->name('dashboard.index');
        Route::get('/relatorios', [ManagementController::class, 'reports'])->name('reports.index');
        Route::get('/relatorios/exportar.pdf', [ManagementController::class, 'exportReportsPdf'])->name('reports.exportPdf');
    });

    Route::middleware('profile:admin,vendedor')->group(function () {
        Route::get('/vendas', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/vendas/exportar.csv', [SaleController::class, 'exportCsv'])->name('sales.exportCsv');
        Route::get('/vendas/exportar.pdf', [SaleController::class, 'exportPdf'])->name('sales.exportPdf');
        Route::get('/vendas/criar', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/vendas', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/vendas/{sale}/editar', [SaleController::class, 'edit'])->name('sales.edit');
        Route::get('/vendas/{sale}/impressao', [SaleController::class, 'printPreview'])->name('sales.printPreview');
        Route::get('/vendas/{sale}/pdf', [SaleController::class, 'printPdf'])->name('sales.printPdf');
        Route::put('/vendas/{sale}/itens', [SaleController::class, 'updateItems'])->name('sales.updateItems');
        Route::post('/vendas/{sale}/confirmar', [SaleController::class, 'confirm'])->name('sales.confirm');

        Route::get('/orcamentos', [QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/orcamentos/exportar.csv', [QuoteController::class, 'exportCsv'])->name('quotes.exportCsv');
        Route::get('/orcamentos/exportar.pdf', [QuoteController::class, 'exportPdf'])->name('quotes.exportPdf');
        Route::get('/orcamentos/criar', [QuoteController::class, 'create'])->name('quotes.create');
        Route::post('/orcamentos', [QuoteController::class, 'store'])->name('quotes.store');
        Route::get('/orcamentos/{quote}/editar', [QuoteController::class, 'edit'])->name('quotes.edit');
        Route::get('/orcamentos/{quote}/impressao', [QuoteController::class, 'printPreview'])->name('quotes.printPreview');
        Route::get('/orcamentos/{quote}/pdf', [QuoteController::class, 'printPdf'])->name('quotes.printPdf');
        Route::post('/orcamentos/{quote}/email', [QuoteController::class, 'sendEmail'])->name('quotes.sendEmail');
        Route::put('/orcamentos/{quote}', [QuoteController::class, 'update'])->name('quotes.update');
        Route::delete('/orcamentos/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
        Route::post('/orcamentos/{quote}/converter', [QuoteController::class, 'convert'])->name('quotes.convert');
        Route::post('/orcamentos/{quote}/duplicar', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');

        Route::get('/desenhos', [DesignSketchController::class, 'index'])->name('designSketches.index');
        Route::get('/desenhos/novo', [DesignSketchController::class, 'create'])->name('designSketches.create');
        Route::post('/desenhos', [DesignSketchController::class, 'store'])->name('designSketches.store');
        Route::get('/desenhos/{designSketch}/editar', [DesignSketchController::class, 'edit'])->name('designSketches.edit');
        Route::put('/desenhos/{designSketch}', [DesignSketchController::class, 'update'])->name('designSketches.update');
        Route::delete('/desenhos/{designSketch}', [DesignSketchController::class, 'destroy'])->name('designSketches.destroy');

        Route::get('/produtos', [CatalogItemController::class, 'index'])->name('products.index');
        Route::get('/produtos/crud', [CatalogItemController::class, 'indexCrud'])->name('products.crud');
        Route::get('/produtos/criar', [CatalogItemController::class, 'create'])->name('products.create');
        Route::post('/produtos/crud', [CatalogItemController::class, 'store'])->name('products.store');
        Route::get('/produtos/{product}/editar', [CatalogItemController::class, 'edit'])->name('products.edit');
        Route::put('/produtos/{product}', [CatalogItemController::class, 'update'])->name('products.update');
    });

    Route::middleware('profile:admin')->group(function () {
        Route::get('/financeiro/receber', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('/financeiro/receber/exportar.csv', [ReceivableController::class, 'exportCsv'])->name('receivables.exportCsv');
        Route::get('/financeiro/receber/exportar.pdf', [ReceivableController::class, 'exportPdf'])->name('receivables.exportPdf');
        Route::put('/financeiro/receber/{receivable}', [ReceivableController::class, 'update'])->name('receivables.update');
        Route::post('/financeiro/receber/{receivable}/parcelar', [ReceivableController::class, 'split'])->name('receivables.split');
        Route::post('/financeiro/receber/{receivable}/baixar', [ReceivableController::class, 'settle'])->name('receivables.settle');

        Route::get('/financeiro/pagar', [PayableController::class, 'index'])->name('payables.index');
        Route::post('/financeiro/pagar', [PayableController::class, 'store'])->name('payables.store');
        Route::put('/financeiro/pagar/{payable}', [PayableController::class, 'update'])->name('payables.update');
        Route::post('/financeiro/pagar/{payable}/baixar', [PayableController::class, 'settle'])->name('payables.settle');
        Route::post('/financeiro/pagar/{payable}/cancelar', [PayableController::class, 'cancel'])->name('payables.cancel');
        Route::get('/financeiro/pagar/exportar.csv', [PayableController::class, 'exportCsv'])->name('payables.exportCsv');
        Route::get('/financeiro/pagar/exportar.pdf', [PayableController::class, 'exportPdf'])->name('payables.exportPdf');

        Route::get('/financeiro/fluxo-caixa', [CashFlowController::class, 'index'])->name('cashflow.index');
        Route::get('/financeiro/fluxo-caixa/exportar.csv', [CashFlowController::class, 'exportCsv'])->name('cashflow.exportCsv');
        Route::get('/financeiro/fluxo-caixa/exportar.pdf', [CashFlowController::class, 'exportPdf'])->name('cashflow.exportPdf');

        Route::get('/financeiro/dre', [FinancialStatementController::class, 'index'])->name('financialStatement.index');
        Route::get('/financeiro/dre/exportar.csv', [FinancialStatementController::class, 'exportCsv'])->name('financialStatement.exportCsv');
        Route::get('/financeiro/dre/exportar.pdf', [FinancialStatementController::class, 'exportPdf'])->name('financialStatement.exportPdf');
    });

    Route::middleware('profile:admin,estoquista,vendedor')->group(function () {
        Route::get('/estoque', [StockController::class, 'index'])->name('stock.index');
        Route::get('/estoque/historico.csv', [StockController::class, 'exportCsv'])->name('stock.exportCsv');
    });

    Route::middleware('profile:admin,estoquista')->group(function () {
        Route::post('/estoque/entrada', [StockController::class, 'entry'])->name('stock.entry');
        Route::post('/estoque/saida', [StockController::class, 'output'])->name('stock.output');
        Route::post('/estoque/ajuste', [StockController::class, 'adjust'])->name('stock.adjust');

        Route::get('/fornecedores', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/fornecedores/criar', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/fornecedores', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/fornecedores/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('/fornecedores/{supplier}/editar', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/fornecedores/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/fornecedores/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('/fornecedores/{supplier}/reativar', [SupplierController::class, 'restore'])->name('suppliers.restore');

        Route::get('/compras', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/compras/criar', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/compras', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/compras/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/compras/{purchaseOrder}/itens/{purchase_order_item_id}/receber', [PurchaseOrderController::class, 'receiveItem'])->name('purchase-orders.receiveItem');
        Route::patch('/compras/{purchaseOrder}/cancelar', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    Route::middleware('profile:admin,vendedor')->group(function () {
        Route::get('/clientes', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clientes/criar', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clientes', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clientes/{client}/editar', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clientes/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clientes/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    Route::middleware('profile:admin')->group(function () {
        Route::get('/categorias', [CategoryController::class, 'indexCrud'])->name('categories.index');
        Route::get('/categorias/crud', [CategoryController::class, 'indexCrud'])->name('categories.crud');
        Route::get('/categorias/criar', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categorias/crud', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::delete('/produtos/{product}', [CatalogItemController::class, 'destroy'])->name('products.destroy');

        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/criar', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/configuracoes/comerciais', [CommercialSettingsController::class, 'edit'])->name('settings.commercial.edit');
        Route::put('/configuracoes/comerciais', [CommercialSettingsController::class, 'update'])->name('settings.commercial.update');

        Route::get('/configuracoes/sequencias', [SequenceController::class, 'index'])->name('settings.sequences.index');
        Route::get('/configuracoes/sequencias/{sequence}', [SequenceController::class, 'show'])->name('settings.sequences.show');
        Route::get('/configuracoes/sequencias/{sequence}/editar', [SequenceController::class, 'edit'])->name('settings.sequences.edit');
        Route::put('/configuracoes/sequencias/{sequence}', [SequenceController::class, 'update'])->name('settings.sequences.update');
        Route::post('/configuracoes/sequencias/{sequence}/reset', [SequenceController::class, 'reset'])->name('settings.sequences.reset');
    });
});
