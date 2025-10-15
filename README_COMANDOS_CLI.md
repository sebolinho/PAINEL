# 🎉 Comandos CLI para Sincronização TMDB - Guia Rápido

## 🚀 Início Rápido

### Executar Agora (sem configuração):

```bash
# Sincronizar todos os itens pendentes
php artisan tmdb:sync-all-pending

# Sincronizar filmes recentes
php artisan tmdb:sync-recent-movies

# Sincronizar séries recentes
php artisan tmdb:sync-recent-series
```

**Pronto!** Não precisa de chaves secretas, URLs ou configurações especiais.

## 🎯 O Que Foi Implementado?

### Antes ❌
```bash
# Era necessário acessar URLs com chaves secretas
curl "https://seusite.com/cron/sync-all-pending/CHAVE_SECRETA"
```

### Depois ✅
```bash
# Agora basta executar o comando
php artisan tmdb:sync-all-pending
```

## 📦 Estrutura de Arquivos

```
PAINEL/
├── SyncAllPendingCommand.php      → Comando para itens pendentes
├── SyncRecentMoviesCommand.php    → Comando para filmes
├── SyncRecentSeriesCommand.php    → Comando para séries
├── TmdbController (7).php         → Controller atualizado
│
└── 📚 Documentação/
    ├── README_COMANDOS_CLI.md     → Este arquivo (início rápido)
    ├── CRON_CLI_README.md         → Guia completo de uso
    ├── MIGRATION_GUIDE.md         → Como migrar de HTTP para CLI
    ├── ARCHITECTURE.md            → Arquitetura técnica
    └── CHANGES.md                 → Detalhes das mudanças
```

## ⚙️ Instalação em Projeto Laravel

### Passo 1: Copiar Comandos
```bash
# Criar diretório se não existir
mkdir -p app/Console/Commands

# Copiar comandos
cp SyncAllPendingCommand.php app/Console/Commands/
cp SyncRecentMoviesCommand.php app/Console/Commands/
cp SyncRecentSeriesCommand.php app/Console/Commands/
```

### Passo 2: Verificar Instalação
```bash
# Listar comandos disponíveis
php artisan list | grep tmdb
```

Você deve ver:
```
tmdb:sync-all-pending      Sincroniza todos os itens pendentes do calendário TMDB
tmdb:sync-recent-movies    Sincroniza filmes recentes do TMDB
tmdb:sync-recent-series    Sincroniza séries recentes do TMDB
```

### Passo 3: Testar
```bash
# Executar um teste
php artisan tmdb:sync-all-pending
```

## 🕐 Configurar Cron Jobs

### Exemplo Básico
```bash
# Abrir editor de cron
crontab -e

# Adicionar estas linhas (ajustar caminho do projeto)
0 2 * * * cd /var/www/html && php artisan tmdb:sync-all-pending
0 */6 * * * cd /var/www/html && php artisan tmdb:sync-recent-movies
0 */6 * * * cd /var/www/html && php artisan tmdb:sync-recent-series
```

### Explicação dos Horários
- `0 2 * * *` = Diariamente às 2h da manhã
- `0 */6 * * *` = A cada 6 horas
- `0 */4 * * *` = A cada 4 horas (alternativa)

### Com Logs
```bash
# Salvar saída em arquivo de log
0 2 * * * cd /var/www/html && php artisan tmdb:sync-all-pending >> /var/log/tmdb-sync.log 2>&1
```

## 📊 O Que Você Verá

### Durante a Execução
```
Iniciando sincronização de todos os itens pendentes...
Encontrados 150 itens pendentes.
 150/150 [============================] 100%
Sincronização (Calendário) via CLI concluída. 
Criados: 120, Atualizados: 15, Ignorados: 10, Falhas: 5.
```

### Nos Logs do Laravel
```
[2025-10-15 00:00:00] INFO: Sincronização via CLI concluída. 
                            Criados: 120, Atualizados: 15, 
                            Ignorados: 10, Falhas: 5.
```

## 🛠️ Comandos Úteis

### Ver Logs em Tempo Real
```bash
tail -f storage/logs/laravel.log
```

### Ver Logs do Cron
```bash
# Ubuntu/Debian
tail -f /var/log/syslog | grep CRON

# CentOS/RedHat
tail -f /var/log/cron
```

### Verificar Status do Cron
```bash
# Ver cron jobs configurados
crontab -l

# Ver serviço do cron
sudo systemctl status cron
```

### Executar com Verbose (mais detalhes)
```bash
php artisan tmdb:sync-all-pending -v
php artisan tmdb:sync-all-pending -vv
php artisan tmdb:sync-all-pending -vvv
```

## ✅ Checklist de Implementação

Após instalar os comandos:

- [ ] Comandos aparecem em `php artisan list`
- [ ] Teste manual funciona sem erros
- [ ] Configuração do TMDB está no `.env`
- [ ] Cron jobs foram configurados
- [ ] Logs estão sendo escritos
- [ ] Primeiras sincronizações foram bem-sucedidas

## 🔍 Solução de Problemas Comuns

### Comando não encontrado
```bash
# Solução 1: Verificar se arquivos estão no lugar certo
ls -la app/Console/Commands/Sync*.php

# Solução 2: Limpar cache
php artisan cache:clear
php artisan config:clear
```

### Erro de permissão
```bash
# Dar permissões adequadas
chmod -R 755 app/Console/Commands/
chmod +x artisan
```

### TMDB API não configurada
```bash
# Verificar .env
grep TMDB .env

# Deve ter:
# TMDB_API_KEY=sua_chave_aqui
# TMDB_LANGUAGE=pt-BR
```

### Timeout ou execução lenta
```bash
# Verificar configuração de timeout
php -i | grep max_execution_time

# Se necessário, ajustar no php.ini
max_execution_time = 3600
```

## 📚 Documentação Adicional

Para informações mais detalhadas, consulte:

| Documento | Descrição |
|-----------|-----------|
| **CRON_CLI_README.md** | Guia completo dos comandos CLI |
| **MIGRATION_GUIDE.md** | Como migrar de HTTP para CLI |
| **ARCHITECTURE.md** | Arquitetura técnica detalhada |
| **CHANGES.md** | Lista de mudanças implementadas |

## 🎓 Exemplos de Uso

### Sincronização Manual Completa
```bash
# Executar todos os comandos sequencialmente
php artisan tmdb:sync-all-pending && \
php artisan tmdb:sync-recent-movies && \
php artisan tmdb:sync-recent-series
```

### Script de Backup Antes da Sincronização
```bash
#!/bin/bash
# sync-with-backup.sh

# Backup do banco
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# Sincronizar
php artisan tmdb:sync-all-pending

echo "Sincronização concluída!"
```

### Notificação por Email Após Sincronização
```bash
#!/bin/bash
# sync-with-notification.sh

OUTPUT=$(php artisan tmdb:sync-all-pending)
echo "$OUTPUT" | mail -s "TMDB Sync Report" admin@example.com
```

## 🔄 Compatibilidade

### Rotas HTTP Antigas (ainda funcionam)
```php
// web.php - compatibilidade retroativa
Route::get('/cron/sync-all-pending/{key}', ...);
Route::get('/cron/sync-recent-movies/{key}', ...);
Route::get('/cron/sync-recent-series/{key}', ...);
```

### Quando Usar Cada Método

| Situação | Recomendação |
|----------|--------------|
| Servidor próprio | ✅ CLI (mais seguro) |
| Acesso SSH disponível | ✅ CLI (melhor controle) |
| Hospedagem compartilhada | ⚠️ HTTP (se CLI não disponível) |
| Execução automatizada | ✅ CLI (via cron) |
| Teste manual | ✅ CLI (feedback visual) |

## 🌟 Vantagens do Método CLI

| Característica | HTTP | CLI |
|----------------|------|-----|
| Segurança | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Performance | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Facilidade | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Feedback Visual | ⭐ | ⭐⭐⭐⭐⭐ |
| Controle | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| Logs | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

## 💡 Dicas e Truques

### Executar em Background
```bash
# Executar e continuar usando o terminal
nohup php artisan tmdb:sync-all-pending &
```

### Agendar Para Horário de Baixo Tráfego
```bash
# Executar às 3h da manhã (horário de menor uso)
0 3 * * * cd /var/www/html && php artisan tmdb:sync-all-pending
```

### Receber Notificação Apenas em Caso de Erro
```bash
# Só envia email se houver erro
0 2 * * * cd /var/www/html && php artisan tmdb:sync-all-pending || echo "Erro na sincronização" | mail -s "ERRO TMDB" admin@example.com
```

### Limitar Uso de Recursos
```bash
# Usar nice para dar prioridade menor
nice -n 19 php artisan tmdb:sync-all-pending
```

## 📞 Suporte

Se encontrar problemas:

1. ✅ Verifique os logs: `storage/logs/laravel.log`
2. ✅ Consulte a documentação completa em `CRON_CLI_README.md`
3. ✅ Verifique configuração do TMDB no `.env`
4. ✅ Teste manualmente antes de configurar cron

## 🎉 Conclusão

Você agora tem um sistema robusto de sincronização TMDB via CLI que é:
- 🔒 Mais seguro
- 🚀 Mais rápido
- 📊 Mais transparente
- 🛠️ Mais fácil de manter

**Comece agora:**
```bash
php artisan tmdb:sync-all-pending
```

---

**Criado em:** Outubro 2025  
**Versão:** 1.0  
**Status:** ✅ Pronto para produção
