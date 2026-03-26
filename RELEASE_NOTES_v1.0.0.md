# v1.0.0 - Entrega base completa do Alumínio ERP

## Resumo

Primeira release estável com cobertura de fluxos comerciais, estoque, financeiro, segurança e operação.

## O que foi entregue

### Comercial e operação

- Gestão de clientes e fornecedores
- Catálogo de produtos com atributos técnicos
- Orçamentos com itens e desenho técnico
- Conversão de orçamento em venda
- Fluxos web e API para operação diária

### Estoque

- Movimentações de estoque por evento de negócio
- Regras para confirmação de venda com validação de saldo
- Relatórios operacionais de estoque

### Financeiro

- Contas a receber
- Contas a pagar
- Fluxo de caixa
- DRE e relatórios avançados por período/categoria

### Segurança e governança

- Controle por perfis (`admin`, `vendedor`, `estoquista`, `operador`)
- Rate limiting para login e APIs autenticadas
- Auditoria persistida para ações críticas

### Operação e confiabilidade

- Middleware de observabilidade com request id
- Endpoints operacionais: health/readiness/preflight/metrics
- Rotina de backup/list/restore
- Verificação de integridade de backup com checksum SHA-256
- Comandos operacionais via console

## Qualidade

- Suíte automatizada abrangente (Feature + Unit)
- Status de validação: 129 testes passando / 646 assertions

## Notas de implantação

1. Subir containers e dependências
2. Gerar chave da aplicação
3. Rodar migrations e seeders
4. Executar suíte de testes
5. Validar endpoints operacionais em ambiente alvo
