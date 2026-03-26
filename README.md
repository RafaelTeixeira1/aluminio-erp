# Alumínio ERP

Sistema ERP comercial e operacional para distribuidora de alumínio/vidros, com foco em vendas, estoque, financeiro, segurança e operação.

## Visão geral

Este projeto foi desenvolvido com Laravel para atender um fluxo de ponta a ponta:

- Gestão de clientes e fornecedores
- Catálogo de produtos com dados técnicos
- Orçamentos com itens e desenho técnico
- Conversão de orçamento em venda
- Movimentação e controle de estoque
- Contas a pagar, contas a receber e fluxo de caixa
- Relatórios gerenciais (incluindo DRE)
- API com controle de acesso por perfil
- Observabilidade, auditoria e rotinas operacionais

## Stack

- PHP 8 + Laravel
- Blade + Vite
- SQLite/MySQL
- Docker + Docker Compose
- PHPUnit (testes de unidade e integração/feature)

## Destaques técnicos

- Segurança por perfis (`admin`, `vendedor`, `estoquista`, `operador`)
- Rate limiting para autenticação e APIs protegidas
- Trilha de auditoria para ações críticas
- Endpoints operacionais:
  - health
  - readiness
  - preflight
  - metrics
  - backup/list/verify
- Backup/restore com validação de integridade (checksum SHA-256)

## Arquitetura resumida

- `src/app/Http/Controllers/Web`: fluxos web
- `src/app/Http/Controllers/Api`: endpoints da API
- `src/app/Services`: regras e orquestração de domínio
- `src/database/migrations`: evolução de banco
- `src/tests/Feature`: cenários de negócio, segurança e operações
- `docker-compose.yml` + `scripts/dapp`: execução padronizada em container

## Como rodar localmente (Docker)

```bash
docker compose up -d --build
./scripts/dapp php artisan key:generate
./scripts/dapp php artisan migrate --force
./scripts/dapp php artisan db:seed --force
```

Aplicação web:

- `http://localhost:8080`

## Como rodar sem Docker

```bash
cd src
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

## Testes

```bash
./scripts/dapp php artisan test
```

Status atual da suíte:

- 129 testes passando
- 646 assertions

## API e perfis de acesso

As rotas de API usam autenticação por token e autorização por perfil.

- `admin`: acesso completo (inclusive financeiro)
- `vendedor`: operação comercial com restrição de campos financeiros
- `estoquista`: operação de estoque
- `operador`: acesso operacional restrito

## Screenshots

Logo do projeto:

![Logo do projeto](src/public/images/company-logo.png)

Sugestão para portfólio: incluir imagens reais das telas em `docs/screenshots/` (dashboard, orçamento, vendas, estoque, financeiro e métricas operacionais).

## Release

- Tag publicada: `v1.0.0`
- Notas prontas: `RELEASE_NOTES_v1.0.0.md`

## Estrutura do repositório

```text
.
├── docker/
├── scripts/
├── src/
│   ├── app/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   └── tests/
└── docker-compose.yml
```

## Autor

Rafael Teixeira
