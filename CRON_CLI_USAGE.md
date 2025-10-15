# Comandos CLI para Cron Jobs

Agora você pode executar os cron jobs através da linha de comando (CLI) em vez de acessar URLs HTTP.

## Comandos Disponíveis

### 1. Sincronizar todos os itens pendentes

```bash
php artisan cron:sync-all-pending
```

Este comando sincroniza todos os itens pendentes do calendário.

### 2. Sincronizar filmes recentes

```bash
php artisan cron:sync-recent-movies
```

Este comando sincroniza os filmes recentes do TMDB.

### 3. Sincronizar séries recentes

```bash
php artisan cron:sync-recent-series
```

Este comando sincroniza as séries recentes do TMDB.

## Configuração

Certifique-se de que a variável `CRON_SYNC_KEY` está configurada no arquivo `.env`:

```env
CRON_SYNC_KEY=sua_chave_secreta_aqui
```

## Agendamento com Cron

Para executar automaticamente via cron, adicione as seguintes linhas ao seu crontab:

```cron
# Sincronizar itens pendentes a cada hora
0 * * * * cd /caminho/para/seu/projeto && php artisan cron:sync-all-pending >> /dev/null 2>&1

# Sincronizar filmes recentes a cada 6 horas
0 */6 * * * cd /caminho/para/seu/projeto && php artisan cron:sync-recent-movies >> /dev/null 2>&1

# Sincronizar séries recentes a cada 6 horas
30 */6 * * * cd /caminho/para/seu/projeto && php artisan cron:sync-recent-series >> /dev/null 2>&1
```

## Listar todos os comandos

Para ver todos os comandos disponíveis:

```bash
php artisan list
```

Para ver ajuda sobre um comando específico:

```bash
php artisan help cron:sync-all-pending
```
