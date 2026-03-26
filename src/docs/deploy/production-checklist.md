# Checklist de Producao

## 1) Preparacao de ambiente

- Provisionar banco MySQL/MariaDB dedicado
- Configurar segredo forte para APP_KEY
- Criar usuario de banco com privilegios minimos
- Configurar HTTPS no balanceador/proxy
- Configurar backup de volume do banco

## 2) Configuracao da aplicacao

- Copiar `src/.env.production.example` para `.env`
- Ajustar credenciais reais de banco e SMTP
- Garantir `APP_DEBUG=false`
- Garantir `LOG_LEVEL=warning` ou `error`
- Validar timezone e locale do servidor

## 3) Build e migracoes

No diretorio `src`:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate --force
php artisan migrate --force
```

## 4) Otimizacoes Laravel

No diretorio `src`:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 5) Operacao e jobs

- Subir worker de fila com restart supervisionado
- Configurar cron para o scheduler Laravel
- Validar comandos operacionais:
  - `php artisan ops:preflight`
  - `php artisan ops:backup --label=deploy`
  - `php artisan ops:backup:verify <arquivo>`

Exemplo de cron:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

## 6) Validacao pos-deploy

- Rodar smoke tests das rotas criticas
- Validar status de operacao:
  - `GET /api/operacao/health`
  - `GET /api/operacao/readiness`
  - `GET /api/operacao/preflight`
- Confirmar logs sem erros criticos
- Confirmar uso de backup valido mais recente

## 7) Rollback

- Manter release anterior disponivel
- Manter backup verificado antes do deploy
- Em incidente:
  - Restaurar backup validado
  - Reaplicar release anterior
  - Executar preflight novamente
