# Deploy na Render (Laravel + Docker)

Este guia foi feito para publicar o projeto na Render com:

- 1 Web Service (aplicacao)
- 1 Worker (fila)
- 1 Cron Job (scheduler)

## 1) O que voce precisa

- Repositorio no GitHub (ja pronto)
- Conta na Render
- Banco MySQL externo (Render nao oferece MySQL gerenciado)

Sugestoes de MySQL gerenciado:

- PlanetScale
- Aiven MySQL
- Neon + compatibilidade MySQL nao se aplica, use apenas MySQL real

## 2) Arquivos ja preparados no projeto

- `docker/Dockerfile.render`
- `render.yaml`

## 3) Criar os servicos pela Blueprint

1. Na Render, clique em `New +`.
2. Escolha `Blueprint`.
3. Selecione este repositorio.
4. A Render vai ler `render.yaml` e criar:
   - `aluminio-erp-web`
   - `aluminio-erp-queue`
   - `aluminio-erp-scheduler`

O blueprint esta configurado para buscar segredos no Environment Group:

- `aluminio-erp-prod-secrets`

## 4) Criar Environment Group de segredos

No painel da Render:

1. Entre em `Environment Groups`.
2. Clique em `+ New Environment Group`.
3. Nome: `aluminio-erp-prod-secrets`.
4. Adicione as chaves abaixo.

## 5) Variaveis de ambiente (key/value)

Adicione estes segredos no grupo `aluminio-erp-prod-secrets`:

Obrigatorias:

- `APP_URL` = URL publica do web service (ex: `https://aluminio-erp-web.onrender.com`)
- `APP_KEY` = chave Laravel (formato `base64:...`)
- `DB_HOST` = host do MySQL
- `DB_DATABASE` = nome do banco
- `DB_USERNAME` = usuario do banco
- `DB_PASSWORD` = senha do banco
- `MAIL_HOST` = host SMTP
- `MAIL_USERNAME` = usuario SMTP
- `MAIL_PASSWORD` = senha SMTP
- `MAIL_FROM_ADDRESS` = email remetente

Depois, linke esse grupo aos 3 servicos:

- `aluminio-erp-web`
- `aluminio-erp-queue`
- `aluminio-erp-scheduler`

Ja definidos no blueprint (nao sensiveis):

- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql`
- `DB_PORT=3306`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`

## 6) Como gerar APP_KEY

Opcao simples local:

1. No seu ambiente local, com o projeto rodando:

```bash
cd src
php artisan key:generate --show
```

2. Copie o valor (ex: `base64:...`) e cole em `APP_KEY` na Render.

## 7) Migracoes no primeiro deploy

Depois que o Web Service subir, abra `Shell` no servico web e rode:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8) Validacao rapida

No navegador:

- `https://SEU-WEB-SERVICE/login`

No shell da Render:

```bash
php artisan ops:preflight
php artisan ops:backup --label=render-primeiro-deploy
```

## 9) Dominio proprio (cliente)

1. No servico web, abra `Settings > Custom Domains`.
2. Adicione `app.seudominio.com.br`.
3. Configure o DNS conforme instrucoes da Render.
4. Aguarde SSL automatico.

## 10) Problemas comuns

- Erro 500 apos deploy: geralmente `APP_KEY` vazio ou credencial de DB incorreta.
- Login sem sessao: confirme `APP_URL` correto e HTTPS.
- Fila nao processa: confirme worker `aluminio-erp-queue` em status `Running`.
- Tarefas diarias nao rodam: confirme cron `aluminio-erp-scheduler` ativo.
