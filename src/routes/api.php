<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CashFlowController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientAnalysisController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\PayableController;
use App\Http\Controllers\Api\PrintController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ReceivableController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SequenceController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

Route::middleware(['api.token', 'throttle:api-auth', 'api.request.log'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/resumo', [DashboardController::class, 'summary'])->middleware('profile:admin,vendedor,estoquista,operador');
    Route::get('/dashboard/atividades', [DashboardController::class, 'activities'])->middleware('profile:admin,vendedor,estoquista,operador');
    Route::get('/dashboard/feed', [DashboardController::class, 'feed'])->middleware('profile:admin,vendedor,estoquista,operador');
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->middleware('profile:admin,vendedor,estoquista,operador');

    Route::get('/usuarios', [UserController::class, 'index'])->middleware('profile:admin');
    Route::get('/usuarios/{usuario}', [UserController::class, 'show'])->middleware('profile:admin');
    Route::post('/usuarios', [UserController::class, 'store'])->middleware('profile:admin');
    Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->middleware('profile:admin');
    Route::patch('/usuarios/{usuario}/status', [UserController::class, 'setStatus'])->middleware('profile:admin');

    Route::get('/clientes', [ClientController::class, 'index']);
    Route::get('/clientes/{cliente}', [ClientController::class, 'show']);
    Route::get('/clientes/{cliente}/historico', [ClientController::class, 'history']);
    Route::post('/clientes', [ClientController::class, 'store'])->middleware('profile:admin,vendedor');
    Route::put('/clientes/{cliente}', [ClientController::class, 'update'])->middleware('profile:admin,vendedor');
    Route::delete('/clientes/{cliente}', [ClientController::class, 'destroy'])->middleware('profile:admin,vendedor');

    Route::get('/fornecedores', [SupplierController::class, 'index'])->middleware('profile:admin,estoquista');
    Route::get('/fornecedores/{fornecedor}', [SupplierController::class, 'show'])->middleware('profile:admin,estoquista');
    Route::post('/fornecedores', [SupplierController::class, 'store'])->middleware('profile:admin,estoquista');
    Route::put('/fornecedores/{fornecedor}', [SupplierController::class, 'update'])->middleware('profile:admin,estoquista');
    Route::delete('/fornecedores/{fornecedor}', [SupplierController::class, 'destroy'])->middleware('profile:admin');

    Route::get('/compras', [PurchaseOrderController::class, 'index'])->middleware('profile:admin,estoquista');
    Route::get('/compras/{compra}', [PurchaseOrderController::class, 'show'])->middleware('profile:admin,estoquista');
    Route::post('/compras', [PurchaseOrderController::class, 'store'])->middleware('profile:admin,estoquista');
    Route::post('/compras/{compra}/itens/{item}/receber', [PurchaseOrderController::class, 'receiveItem'])->middleware('profile:admin,estoquista');
    Route::post('/compras/{compra}/cancelar', [PurchaseOrderController::class, 'cancel'])->middleware('profile:admin');

    Route::get('/categorias', [CategoryController::class, 'index']);
    Route::get('/categorias/{categoria}', [CategoryController::class, 'show']);
    Route::post('/categorias', [CategoryController::class, 'store'])->middleware('profile:admin');
    Route::put('/categorias/{categoria}', [CategoryController::class, 'update'])->middleware('profile:admin');
    Route::delete('/categorias/{categoria}', [CategoryController::class, 'destroy'])->middleware('profile:admin');

    Route::get('/produtos', [ProductController::class, 'index']);
    Route::get('/produtos/{produto}', [ProductController::class, 'show']);
    Route::post('/produtos', [ProductController::class, 'store'])->middleware('profile:admin,vendedor');
    Route::put('/produtos/{produto}', [ProductController::class, 'update'])->middleware('profile:admin,vendedor');
    Route::delete('/produtos/{produto}', [ProductController::class, 'destroy'])->middleware('profile:admin');

    Route::get('/estoque', [StockController::class, 'index']);
    Route::get('/estoque/historico', [StockController::class, 'history']);
    Route::post('/estoque/entrada', [StockController::class, 'entry'])->middleware('profile:admin,estoquista');
    Route::post('/estoque/ajuste', [StockController::class, 'adjust'])->middleware('profile:admin,estoquista');
    Route::post('/estoque/saida', [StockController::class, 'output'])->middleware('profile:admin,estoquista');

    Route::get('/orcamentos', [QuoteController::class, 'index']);
    Route::post('/orcamentos/expirar-vencidos', [QuoteController::class, 'expireOverdue'])->middleware('profile:admin');
    Route::get('/orcamentos/{orcamento}', [QuoteController::class, 'show']);
    Route::get('/orcamentos/{orcamento}/pdf', [PrintController::class, 'quotePdf']);
    Route::post('/orcamentos', [QuoteController::class, 'store'])->middleware('profile:admin,vendedor');
    Route::put('/orcamentos/{orcamento}', [QuoteController::class, 'update'])->middleware('profile:admin,vendedor');
    Route::delete('/orcamentos/{orcamento}', [QuoteController::class, 'destroy'])->middleware('profile:admin,vendedor');
    Route::put('/orcamentos/{orcamento}/itens', [QuoteController::class, 'replaceItems'])->middleware('profile:admin,vendedor');
    Route::post('/orcamentos/{orcamento}/desenhos', [QuoteController::class, 'addPieceDesigns'])->middleware('profile:admin,vendedor');
    Route::post('/orcamentos/{orcamento}/converter', [QuoteController::class, 'convertToSale'])->middleware('profile:admin,vendedor');

    Route::get('/vendas', [SaleController::class, 'index']);
    Route::get('/vendas/{venda}', [SaleController::class, 'show']);
    Route::get('/vendas/{venda}/pdf', [PrintController::class, 'salePdf']);
    Route::post('/vendas', [SaleController::class, 'store'])->middleware('profile:admin,vendedor');
    Route::put('/vendas/{venda}/itens', [SaleController::class, 'replaceItems'])->middleware('profile:admin,vendedor');
    Route::post('/vendas/{venda}/confirmar', [SaleController::class, 'confirm'])->middleware('profile:admin,vendedor');

    Route::get('/sequencias', [SequenceController::class, 'index'])->middleware('profile:admin');
    Route::get('/sequencias/{code}', [SequenceController::class, 'show'])->middleware('profile:admin');
    Route::put('/sequencias/{code}', [SequenceController::class, 'update'])->middleware('profile:admin');
    Route::post('/sequencias/{code}/reset', [SequenceController::class, 'reset'])->middleware('profile:admin');
    Route::get('/sequencias/{code}/historico', [SequenceController::class, 'history'])->middleware('profile:admin');

    Route::get('/relatorios/vendas', [ReportController::class, 'salesByPeriod'])->middleware('profile:admin,operador');
    Route::get('/relatorios/produtos-mais-vendidos', [ReportController::class, 'bestSellingProducts'])->middleware('profile:admin,operador');
    Route::get('/relatorios/estoque-baixo', [ReportController::class, 'lowStockProducts']);
    Route::get('/relatorios/faturamento', [ReportController::class, 'revenue'])->middleware('profile:admin,operador');
    Route::get('/relatorios/faturamento/pdf', [ReportController::class, 'revenuePdf'])->middleware('profile:admin');
    Route::get('/relatorios/dre', [ReportController::class, 'dre'])->middleware('profile:admin');
    Route::get('/relatorios/margem-por-categoria', [ReportController::class, 'marginByCategory'])->middleware('profile:admin');
    Route::get('/relatorios/lucro-por-periodo', [ReportController::class, 'profitByPeriod'])->middleware('profile:admin');

    Route::get('/contas-a-pagar', [PayableController::class, 'index'])->middleware('profile:admin');
    Route::get('/contas-a-pagar/resumo', [PayableController::class, 'summary'])->middleware('profile:admin');
    Route::post('/contas-a-pagar', [PayableController::class, 'store'])->middleware('profile:admin');
    Route::get('/contas-a-pagar/{conta}', [PayableController::class, 'show'])->middleware('profile:admin');
    Route::put('/contas-a-pagar/{conta}', [PayableController::class, 'update'])->middleware('profile:admin');
    Route::post('/contas-a-pagar/{conta}/baixar', [PayableController::class, 'settle'])->middleware('profile:admin');

    Route::get('/contas-a-receber', [ReceivableController::class, 'index'])->middleware('profile:admin');
    Route::get('/contas-a-receber/resumo', [ReceivableController::class, 'summary'])->middleware('profile:admin');
    Route::get('/contas-a-receber/{titulo}', [ReceivableController::class, 'show'])->middleware('profile:admin');
    Route::post('/contas-a-receber/{titulo}/receber', [ReceivableController::class, 'settle'])->middleware('profile:admin');
    Route::get('/clientes/{cliente}/contas-a-receber', [ReceivableController::class, 'byClient'])->middleware('profile:admin');

    Route::get('/fluxo-caixa', [CashFlowController::class, 'index'])->middleware('profile:admin');
    Route::get('/fluxo-caixa/resumo', [CashFlowController::class, 'summary'])->middleware('profile:admin');
    Route::get('/fluxo-caixa/por-tipo', [CashFlowController::class, 'byType'])->middleware('profile:admin');
    Route::get('/fluxo-caixa/por-origem', [CashFlowController::class, 'byOrigin'])->middleware('profile:admin');
    Route::post('/fluxo-caixa', [CashFlowController::class, 'store'])->middleware('profile:admin');
    Route::get('/fluxo-caixa/{entrada}', [CashFlowController::class, 'show'])->middleware('profile:admin');
    Route::delete('/fluxo-caixa/{entrada}', [CashFlowController::class, 'destroy'])->middleware('profile:admin');

    Route::get('/clientes/{cliente}/analise', [ClientAnalysisController::class, 'analysis'])->middleware('profile:admin');
    Route::get('/clientes/{cliente}/timeline', [ClientAnalysisController::class, 'timeline'])->middleware('profile:admin');
    Route::get('/clientes/{cliente}/faturamento', [ClientAnalysisController::class, 'revenue'])->middleware('profile:admin');

    Route::get('/auditoria', [AuditLogController::class, 'index'])->middleware('profile:admin');

    Route::get('/operacao/health', [OperationsController::class, 'health'])->middleware('profile:admin');
    Route::get('/operacao/readiness', [OperationsController::class, 'readiness'])->middleware('profile:admin');
    Route::get('/operacao/preflight', [OperationsController::class, 'preflight'])->middleware('profile:admin');
    Route::get('/operacao/metrics', [OperationsController::class, 'metrics'])->middleware('profile:admin');
    Route::get('/operacao/backups', [OperationsController::class, 'backups'])->middleware('profile:admin');
    Route::post('/operacao/backup', [OperationsController::class, 'backup'])->middleware('profile:admin');
    Route::post('/operacao/backup/verificar', [OperationsController::class, 'verifyBackup'])->middleware('profile:admin');
});
