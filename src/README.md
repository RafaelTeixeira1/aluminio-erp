# SD Aluminíos - Sistema Comercial e Operacional

Sistema web para gestão de:

- Clientes
- Catálogo de produtos e características técnicas
- Estoque e movimentações
- Orçamentos com desenho técnico ilustrativo
- Conversão de orçamento em venda
- Financeiro (receber/pagar/fluxo de caixa/DRE)
- Relatórios por perfil de acesso

## Tecnologias

- PHP 8+
- Laravel
- Blade + Vite
- Banco relacional (SQLite/MySQL)
- Docker Compose (recomendado)

## Perfis de usuário

- `admin`: acesso completo
- `vendedor`: operação comercial (clientes, produtos, orçamentos, vendas, consulta de estoque)
- `estoquista`: operação de estoque
- `operador`: acesso operacional restrito

Observação: indicadores financeiros e receita são restritos ao perfil administrativo.

## Subir o projeto (recomendado com Docker)

Na raiz do repositório:

```bash
docker compose up -d --build
```

Com o helper do projeto:

```bash
./scripts/dapp php artisan key:generate
./scripts/dapp php artisan migrate --force
./scripts/dapp php artisan db:seed --force
```

## Setup sem Docker

Dentro de `src/`:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

## Importante: migrations

Este projeto evolui com frequência. Um banco local antigo pode não ter tabelas novas (ex.: financeiro e desenhos).

Sempre rode migrations antes de validar funcionalidades:

```bash
./scripts/dapp php artisan migrate --force
```

Sem isso, partes do sistema podem parecer com defeito quando, na prática, o banco está desatualizado.

## Fluxo principal de uso

1. Cadastrar/atualizar clientes
2. Manter catálogo de produtos (com dados técnicos)
3. Montar orçamento com itens
4. (Opcional) inserir desenho técnico ilustrativo no próprio orçamento
5. Converter orçamento em venda
6. Confirmar venda e acompanhar impactos em estoque/financeiro

## Regras de negócio relevantes

- O mesmo orçamento não pode ser convertido em venda mais de uma vez.
- Conversão duplicada é bloqueada por validação de regra de negócio.

## Contrato da API por perfil (visibilidade financeira)

Para endpoints acessíveis ao perfil `vendedor`, campos financeiros são mascarados no payload.

### Sinalização de contrato

- As respostas incluem `can_view_financial`.
- `can_view_financial = true`: perfil com visão financeira.
- `can_view_financial = false`: perfil sem visão financeira (ex.: vendedor).

### Política de mascaramento

- Valores financeiros sensíveis são retornados como `null` quando `can_view_financial = false`.
- Dados operacionais continuam disponíveis normalmente (status, datas, cliente, estoque, quantidades, etc.).

### Endpoints com mascaramento ativo

- Dashboard API:
	- `GET /api/dashboard/resumo`
	- `GET /api/dashboard/atividades`
	- `GET /api/dashboard/feed`

- Orçamentos API:
	- `GET /api/orcamentos`
	- `GET /api/orcamentos/{orcamento}`
	- `POST /api/orcamentos`
	- `PUT /api/orcamentos/{orcamento}`
	- `PUT /api/orcamentos/{orcamento}/itens`
	- `POST /api/orcamentos/{orcamento}/desenhos`
	- `POST /api/orcamentos/{orcamento}/converter`

- Vendas API:
	- `GET /api/vendas`
	- `GET /api/vendas/{venda}`
	- `POST /api/vendas`
	- `PUT /api/vendas/{venda}/itens`
	- `POST /api/vendas/{venda}/confirmar`

- Produtos API:
	- `GET /api/produtos`
	- `GET /api/produtos/{produto}`
	- `POST /api/produtos`
	- `PUT /api/produtos/{produto}`

- Cliente API:
	- `GET /api/clientes/{cliente}/historico`

- Relatórios API:
	- `GET /api/relatorios/estoque-baixo`

### Campos tipicamente mascarados

- Em vendas/orçamentos: `subtotal`, `discount`, `total`.
- Em itens de venda/orçamento: `unit_price`, `line_total`.
- Em produtos e estoque baixo: `price`.
- Em timelines/feed: `amount` e totais de atividades financeiras.

### Recomendação para consumidores da API

- Sempre validar `can_view_financial` antes de renderizar blocos monetários.
- Tratar campos monetários como opcionais (`null`) mesmo quando o endpoint historicamente retornava número.

## Testes

Rodar suíte completa:

```bash
./scripts/dapp php artisan test
```

Rodar testes por filtro:

```bash
./scripts/dapp php artisan test --filter='WebQuoteActionsTest'
```

## Estrutura resumida

- `routes/web.php`: rotas web por perfil
- `routes/api.php`: rotas API com middleware de perfil
- `app/Http/Controllers/Web`: controllers da interface web
- `app/Http/Controllers/Api`: controllers da API
- `resources/views`: telas Blade e PDFs
- `database/migrations`: estrutura de banco
- `tests/Feature`: testes de fluxo e permissões

## Observações de arquitetura

- Uploads de item de orçamento usam storage público (`storage/quote-items`).
- Desenho do orçamento utiliza `design_sketches` e pode ser exibido no PDF.
