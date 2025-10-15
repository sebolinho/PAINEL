# Comandos CLI para Cron Jobs

Agora você pode executar os cron jobs através da linha de comando (CLI) em vez de acessar URLs HTTP.

## Melhorias Recentes

✨ **Otimizações de Memória**: Os comandos agora processam dados em chunks de 50 itens para evitar estouro de memória (erro "Allowed memory size exhausted")

✨ **Logging em Tempo Real**: Você pode ver em tempo real quais filmes/séries estão sendo adicionados durante a sincronização

✨ **Garbage Collection**: Limpeza automática de memória entre processamentos

✨ **Limite de Memória**: Memória aumentada automaticamente para 256MB quando necessário

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

## Visualização em Tempo Real

Durante a execução dos comandos, você verá mensagens como:

```
Iniciando sincronização de 100 filmes recentes...
Processando filme TMDB ID: 12345...
✓ Filme criado: 'Nome do Filme' created
Processando filme TMDB ID: 67890...
⊘ Filme ignorado: Movie 'Outro Filme' já existe ignorado
...

Sincronização de filmes via CRON concluída. Criados: 3, Ignorados: 97, Falhas: 0.
```

**Legenda dos símbolos:**
- ✓ = Item criado ou atualizado com sucesso
- ⊘ = Item ignorado (já existe e está atualizado)
- ✗ = Erro ao processar o item

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

## Solução de Problemas

### Erro de Memória Esgotada

Se você ainda encontrar erros como "Allowed memory size of X bytes exhausted", você pode:

1. **Aumentar o limite de memória do PHP** no arquivo `php.ini`:
```ini
memory_limit = 512M
```

2. **Ou aumentar temporariamente via linha de comando**:
```bash
php -d memory_limit=512M artisan cron:sync-recent-movies
```

### Verificar Logs

Os logs são salvos automaticamente. Para visualizar:

```bash
# Ver últimas 50 linhas do log do Laravel
tail -n 50 storage/logs/laravel.log

# Ver logs em tempo real (acompanhar enquanto roda)
tail -f storage/logs/laravel.log
```

### Monitorar Uso de Memória

Para monitorar o uso de memória durante a execução:

```bash
# Em um terminal separado, execute:
watch -n 1 "ps aux | grep 'php artisan cron'"
```
