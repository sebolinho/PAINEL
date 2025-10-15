# Guia de Migração - Cron via HTTP para CLI

## Visão Geral
Este guia ajuda a migrar de execuções cron via HTTP (URLs) para comandos CLI (linha de comando).

## Antes (Método HTTP)

### Como era executado:
```bash
# Via curl ou wget acessando URLs
curl "https://seusite.com/cron/sync-all-pending/SUA_CHAVE_SECRETA"
curl "https://seusite.com/cron/sync-recent-movies/SUA_CHAVE_SECRETA"
curl "https://seusite.com/cron/sync-recent-series/SUA_CHAVE_SECRETA"
```

### Problemas:
1. Necessário configurar chave secreta em `CRON_SYNC_KEY`
2. Endpoint HTTP exposto publicamente
3. Risco de segurança se a chave vazar
4. Dependente de servidor web funcionando
5. Sem feedback visual de progresso
6. Timeouts do servidor web podem interromper execuções longas

## Depois (Método CLI) ✨

### Como executar agora:
```bash
# Diretamente via PHP Artisan
php artisan tmdb:sync-all-pending
php artisan tmdb:sync-recent-movies
php artisan tmdb:sync-recent-series
```

### Vantagens:
1. ✅ Sem necessidade de chave secreta
2. ✅ Não expõe endpoints HTTP
3. ✅ Mais seguro (executado localmente)
4. ✅ Independente do servidor web
5. ✅ Barra de progresso visual
6. ✅ Sem limites de timeout
7. ✅ Melhor controle e logs

## Passo a Passo para Migração

### 1. Instalar os Comandos

Os arquivos já foram criados:
- `SyncAllPendingCommand.php`
- `SyncRecentMoviesCommand.php`
- `SyncRecentSeriesCommand.php`

**Para um projeto Laravel real**, mova-os para:
```bash
mkdir -p app/Console/Commands
mv SyncAllPendingCommand.php app/Console/Commands/
mv SyncRecentMoviesCommand.php app/Console/Commands/
mv SyncRecentSeriesCommand.php app/Console/Commands/
```

### 2. Verificar Comandos Disponíveis

```bash
php artisan list | grep tmdb
```

Você deve ver:
```
tmdb:sync-all-pending      Sincroniza todos os itens pendentes do calendário TMDB
tmdb:sync-recent-movies    Sincroniza filmes recentes do TMDB
tmdb:sync-recent-series    Sincroniza séries recentes do TMDB
```

### 3. Testar Comandos Manualmente

```bash
# Testar cada comando
php artisan tmdb:sync-all-pending
php artisan tmdb:sync-recent-movies
php artisan tmdb:sync-recent-series
```

### 4. Atualizar Cron Jobs

#### Antes (remover):
```bash
# Remover estas linhas do crontab
0 2 * * * curl "https://seusite.com/cron/sync-all-pending/CHAVE" >> /dev/null 2>&1
0 */6 * * * curl "https://seusite.com/cron/sync-recent-movies/CHAVE" >> /dev/null 2>&1
0 */6 * * * curl "https://seusite.com/cron/sync-recent-series/CHAVE" >> /dev/null 2>&1
```

#### Depois (adicionar):
```bash
# Editar crontab
crontab -e

# Adicionar novas linhas (ajustar caminho do projeto)
0 2 * * * cd /var/www/html && php artisan tmdb:sync-all-pending >> /dev/null 2>&1
0 */6 * * * cd /var/www/html && php artisan tmdb:sync-recent-movies >> /dev/null 2>&1
0 */6 * * * cd /var/www/html && php artisan tmdb:sync-recent-series >> /dev/null 2>&1
```

**Importante:** Substitua `/var/www/html` pelo caminho real do seu projeto.

### 5. Remover Chave Secreta (Opcional)

Como não é mais necessária, você pode remover do `.env`:
```bash
# Pode remover esta linha do .env
# CRON_SYNC_KEY=SUA_CHAVE_SECRETA
```

**Nota:** As rotas HTTP antigas ainda funcionam se você quiser manter compatibilidade.

## Comparação Lado a Lado

| Aspecto | HTTP (Antigo) | CLI (Novo) |
|---------|--------------|-----------|
| **Segurança** | ⚠️ Requer chave na URL | ✅ Execução local, sem chave |
| **Exposição** | ⚠️ Endpoint público | ✅ Sem endpoint exposto |
| **Feedback** | ❌ Sem progresso visível | ✅ Barra de progresso |
| **Timeout** | ⚠️ Limitado pelo servidor | ✅ Sem limite |
| **Logs** | ⚠️ Básicos | ✅ Detalhados |
| **Dependência** | ⚠️ Servidor web necessário | ✅ Apenas PHP CLI |
| **Facilidade** | ⚠️ Configuração complexa | ✅ Simples de usar |

## Manter Compatibilidade

Se você ainda precisa das rotas HTTP por algum motivo, elas continuam funcionando:

```php
// Em web.php - ainda disponíveis
Route::get('/cron/sync-all-pending/{key}', [TmdbController::class, 'cronSyncAllPending']);
Route::get('/cron/sync-recent-movies/{key}', [TmdbController::class, 'cronSyncRecentMovies']);
Route::get('/cron/sync-recent-series/{key}', [TmdbController::class, 'cronSyncRecentSeries']);
```

Mas recomendamos migrar para CLI para maior segurança.

## Verificação de Sucesso

Após configurar os novos comandos:

1. ✅ Comandos aparecem em `php artisan list`
2. ✅ Execução manual funciona sem erros
3. ✅ Cron jobs executam automaticamente
4. ✅ Logs mostram execuções bem-sucedidas
5. ✅ Dados sincronizados aparecem no sistema

## Solução de Problemas

### Comando não encontrado
```bash
# Verificar se PHP está no PATH
which php

# Verificar se Artisan funciona
php artisan --version

# Listar todos os comandos
php artisan list
```

### Permissão negada
```bash
# Dar permissão de execução ao artisan
chmod +x artisan

# Verificar permissões do diretório
ls -la
```

### Erro de configuração
```bash
# Verificar configuração do Laravel
php artisan config:cache
php artisan config:clear

# Verificar .env tem as configurações do TMDB
cat .env | grep TMDB
```

## Suporte

Para mais informações, consulte:
- `CRON_CLI_README.md` - Documentação completa dos comandos
- `CHANGES.md` - Detalhes técnicos das mudanças
- Logs do Laravel em `storage/logs/laravel.log`

## Conclusão

A migração para comandos CLI oferece:
- 🔒 Mais segurança
- 🚀 Melhor desempenho
- 📊 Melhor visibilidade
- 🛠️ Mais controle

Recomendamos fortemente fazer a migração assim que possível!
