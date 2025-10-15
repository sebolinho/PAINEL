# Comandos CLI para Sincronização TMDB

Este documento explica como usar os novos comandos CLI (linha de comando) para executar as sincronizações TMDB sem precisar acessar URLs via navegador.

## Comandos Disponíveis

### 1. Sincronizar Todos os Itens Pendentes

Sincroniza todos os itens pendentes do calendário TMDB:

```bash
php artisan tmdb:sync-all-pending
```

Este comando:
- Busca todos os itens marcados como "Pendente" no calendário
- Sincroniza cada item com o TMDB
- Exibe uma barra de progresso durante a execução
- Mostra um resumo ao final com estatísticas (criados, atualizados, ignorados, falhas)

### 2. Sincronizar Filmes Recentes

Sincroniza os filmes recentes do TMDB:

```bash
php artisan tmdb:sync-recent-movies
```

Este comando:
- Busca os filmes mais recentes do TMDB
- Sincroniza cada filme novo com o banco de dados
- Exibe uma barra de progresso durante a execução
- Mostra um resumo ao final com estatísticas

### 3. Sincronizar Séries Recentes

Sincroniza as séries recentes do TMDB:

```bash
php artisan tmdb:sync-recent-series
```

Este comando:
- Busca as séries mais recentes do TMDB
- Sincroniza cada série nova com o banco de dados
- Exibe uma barra de progresso durante a execução
- Mostra um resumo ao final com estatísticas

## Configuração de Cron Jobs

Para executar esses comandos automaticamente via cron, adicione as seguintes linhas ao seu crontab:

```bash
# Sincronizar todos os itens pendentes diariamente às 2h da manhã
0 2 * * * cd /caminho/para/seu/projeto && php artisan tmdb:sync-all-pending >> /dev/null 2>&1

# Sincronizar filmes recentes a cada 6 horas
0 */6 * * * cd /caminho/para/seu/projeto && php artisan tmdb:sync-recent-movies >> /dev/null 2>&1

# Sincronizar séries recentes a cada 6 horas
0 */6 * * * cd /caminho/para/seu/projeto && php artisan tmdb:sync-recent-series >> /dev/null 2>&1
```

**Importante:** Substitua `/caminho/para/seu/projeto` pelo caminho real do seu projeto Laravel.

## Vantagens da Execução via CLI

1. **Sem necessidade de chave secreta**: Não é mais necessário passar uma chave na URL
2. **Mais seguro**: Não expõe endpoints HTTP que podem ser acessados externamente
3. **Melhor controle**: Pode ser executado manualmente ou via cron de forma mais confiável
4. **Feedback visual**: Mostra uma barra de progresso durante a execução
5. **Logs detalhados**: Todos os erros e sucessos são registrados nos logs do Laravel

## Compatibilidade

As rotas HTTP antigas ainda funcionam para compatibilidade retroativa:
- `/cron/sync-all-pending/{key}`
- `/cron/sync-recent-movies/{key}`
- `/cron/sync-recent-series/{key}`

Porém, recomenda-se migrar para os comandos CLI para maior segurança e controle.

## Requisitos

- PHP CLI instalado
- Laravel Artisan configurado
- Permissões adequadas para executar scripts PHP
- Configuração do TMDB (API key e idioma) definida no `.env`

## Verificação de Logs

Todos os comandos registram suas ações nos logs do Laravel. Para verificar os logs:

```bash
tail -f storage/logs/laravel.log
```

## Solução de Problemas

Se os comandos não funcionarem:

1. Verifique se o PHP está no PATH: `php --version`
2. Verifique se o Artisan funciona: `php artisan list`
3. Confirme que a API do TMDB está configurada: verifique o arquivo `.env`
4. Verifique as permissões de escrita no diretório `storage/logs`
5. Consulte os logs para mensagens de erro detalhadas
