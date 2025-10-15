# Arquitetura da Solução - Comandos CLI para Cron TMDB

## Visão Geral da Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                    Usuário / Sistema                         │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
         ┌─────────────────────────────────┐
         │      Artisan CLI Interface      │
         │   php artisan tmdb:sync-*       │
         └─────────────────────────────────┘
                           │
         ┌─────────────────┴─────────────────┐
         ▼                                    ▼
┌──────────────────────┐          ┌──────────────────────┐
│  SyncAllPending      │          │  SyncRecentMovies    │
│  Command             │          │  Command             │
│                      │          │                      │
│  - Progress bar      │          │  - Progress bar      │
│  - Statistics        │          │  - Statistics        │
│  - Error handling    │          │  - Error handling    │
└──────────────────────┘          └──────────────────────┘
         │                                    │
         │                                    │
         └─────────────────┬──────────────────┘
                           ▼
         ┌──────────────────────────────────────┐
         │      SyncRecentSeries Command        │
         │                                      │
         │  - Progress bar                      │
         │  - Statistics                        │
         │  - Error handling                    │
         └──────────────────────────────────────┘
                           │
                           ▼
         ┌──────────────────────────────────────┐
         │      TmdbController                  │
         │                                      │
         │  Public Methods:                     │
         │  • getEnrichedCalendarData()         │
         │  • getRecentMoviesData()             │
         │  • getRecentSeriesData()             │
         │  • store()                           │
         └──────────────────────────────────────┘
                           │
         ┌─────────────────┴─────────────────┐
         ▼                                    ▼
┌──────────────────┐              ┌──────────────────┐
│   TMDB API       │              │   Database       │
│   (External)     │              │   (Laravel)      │
└──────────────────┘              └──────────────────┘
```

## Fluxo de Dados

### 1. Sync All Pending (Sincronizar Todos Pendentes)
```
Comando CLI
    │
    ├──> getEnrichedCalendarData()
    │    │
    │    └──> Busca calendário do TMDB
    │         └──> Retorna itens com status
    │
    ├──> Filtra itens "Pendente"
    │
    └──> Para cada item:
         ├──> store(tmdb_id, type='tv')
         │    └──> Salva no banco de dados
         │
         └──> Atualiza estatísticas
              └──> Exibe progresso
```

### 2. Sync Recent Movies (Sincronizar Filmes Recentes)
```
Comando CLI
    │
    ├──> getRecentMoviesData()
    │    │
    │    └──> Busca filmes recentes do TMDB
    │         └──> Retorna lista de IDs
    │
    └──> Para cada filme:
         ├──> store(tmdb_id, type='movie')
         │    └──> Salva no banco de dados
         │
         └──> Atualiza estatísticas
              └──> Exibe progresso
```

### 3. Sync Recent Series (Sincronizar Séries Recentes)
```
Comando CLI
    │
    ├──> getRecentSeriesData()
    │    │
    │    └──> Busca séries recentes do TMDB
    │         └──> Retorna lista de IDs
    │
    └──> Para cada série:
         ├──> store(tmdb_id, type='tv')
         │    └──> Salva no banco de dados
         │
         └──> Atualiza estatísticas
              └──> Exibe progresso
```

## Componentes Principais

### 1. Comandos Artisan (Console Commands)

**Localização:** `app/Console/Commands/`

#### SyncAllPendingCommand.php
- **Responsabilidade:** Sincronizar itens pendentes do calendário
- **Assinatura:** `tmdb:sync-all-pending`
- **Retorno:** SUCCESS ou FAILURE
- **Recursos:**
  - Barra de progresso
  - Contadores (created, updated, skipped, failed)
  - Logging completo
  - Tratamento de exceções

#### SyncRecentMoviesCommand.php
- **Responsabilidade:** Sincronizar filmes recentes
- **Assinatura:** `tmdb:sync-recent-movies`
- **Retorno:** SUCCESS ou FAILURE
- **Recursos:** (mesmos acima)

#### SyncRecentSeriesCommand.php
- **Responsabilidade:** Sincronizar séries recentes
- **Assinatura:** `tmdb:sync-recent-series`
- **Retorno:** SUCCESS ou FAILURE
- **Recursos:** (mesmos acima)

### 2. Controller (TmdbController)

**Localização:** `app/Http/Controllers/Admin/TmdbController.php`

#### Mudanças Realizadas:
```php
// Antes: private
private function getEnrichedCalendarData(): array { ... }
private function getRecentMoviesData(): array { ... }
private function getRecentSeriesData(): array { ... }

// Depois: public
public function getEnrichedCalendarData(): array { ... }
public function getRecentMoviesData(): array { ... }
public function getRecentSeriesData(): array { ... }
```

#### Métodos Públicos:
- `getEnrichedCalendarData()` - Retorna dados do calendário enriquecidos
- `getRecentMoviesData()` - Retorna dados de filmes recentes
- `getRecentSeriesData()` - Retorna dados de séries recentes
- `store(Request)` - Salva conteúdo no banco de dados

### 3. Rotas Web (Backward Compatibility)

**Localização:** `routes/web.php`

As rotas HTTP antigas ainda funcionam:
```php
Route::get('/cron/sync-all-pending/{key}', [TmdbController::class, 'cronSyncAllPending']);
Route::get('/cron/sync-recent-movies/{key}', [TmdbController::class, 'cronSyncRecentMovies']);
Route::get('/cron/sync-recent-series/{key}', [TmdbController::class, 'cronSyncRecentSeries']);
```

## Padrões de Design Utilizados

### 1. Command Pattern
Os comandos Artisan seguem o padrão Command, encapsulando uma ação em um objeto.

### 2. Facade Pattern
Utilização de Laravel Facades (Log, Http, etc.) para simplificar acesso a serviços.

### 3. Single Responsibility Principle
Cada comando tem uma única responsabilidade bem definida.

### 4. DRY (Don't Repeat Yourself)
Lógica compartilhada está no TmdbController, reutilizada pelos comandos.

## Segurança

### Antes (HTTP):
- ⚠️ Endpoint exposto publicamente
- ⚠️ Necessário proteger com chave secreta
- ⚠️ Vulnerável a ataques de força bruta na chave
- ⚠️ Logs podem conter URLs com chaves

### Depois (CLI):
- ✅ Execução apenas local ou via SSH
- ✅ Sem exposição de endpoints
- ✅ Sem necessidade de chaves
- ✅ Segurança do sistema operacional

## Performance

### Melhorias:
1. **Sem overhead HTTP:** Não há processamento de requisições HTTP
2. **Sem timeout:** Não limitado por timeouts do servidor web
3. **Melhor controle de recursos:** `set_time_limit(3600)` sem conflitos
4. **Execução direta:** Menos camadas entre comando e lógica

## Logs e Monitoramento

### Estrutura de Logs:
```
[timestamp] INFO: Sincronização via CLI concluída. 
            Criados: X, Atualizados: Y, Ignorados: Z, Falhas: W
[timestamp] ERROR: CRON Sync Falhou para TMDB ID {id}: {mensagem}
```

### Localização dos Logs:
- Laravel: `storage/logs/laravel.log`
- Cron: `/var/log/syslog` ou `/var/log/cron`

## Escalabilidade

### Vantagens para Crescimento:
1. **Fácil paralelização:** Pode executar múltiplos comandos simultaneamente
2. **Agendamento flexível:** Cron permite horários customizados
3. **Isolamento:** Falha em um comando não afeta outros
4. **Monitoramento:** Ferramentas padrão Unix podem monitorar

## Deployment

### Checklist de Deploy:
1. ✅ Copiar comandos para `app/Console/Commands/`
2. ✅ Verificar comandos: `php artisan list | grep tmdb`
3. ✅ Testar manualmente cada comando
4. ✅ Configurar cron jobs
5. ✅ Verificar permissões de arquivo
6. ✅ Monitorar logs inicialmente
7. ✅ Documentar para equipe

## Comparação de Recursos

| Recurso | HTTP (Antigo) | CLI (Novo) |
|---------|--------------|-----------|
| Execução | Via URL | Via terminal |
| Autenticação | Chave na URL | Acesso ao servidor |
| Progresso | Nenhum | Barra visual |
| Timeout | 30-60s típico | Ilimitado |
| Logs | Básico | Detalhado |
| Segurança | Média | Alta |
| Manutenção | Complexa | Simples |
| Debugging | Difícil | Fácil |

## Conclusão

A arquitetura implementada oferece:
- 🎯 **Simplicidade** - Comandos diretos e intuitivos
- 🔒 **Segurança** - Execução local sem exposição
- 📊 **Visibilidade** - Progresso e logs detalhados
- 🚀 **Performance** - Sem overhead HTTP
- 🔧 **Manutenibilidade** - Código limpo e bem estruturado
- ♻️ **Reutilização** - Lógica compartilhada no controller
- 🔄 **Compatibilidade** - Não quebra funcionalidade existente

## Próximos Passos Possíveis

### Melhorias Futuras (Opcional):
1. Adicionar parâmetros aos comandos (ex: limite de itens)
2. Criar comando de sincronização completa
3. Adicionar modo verbose para mais detalhes
4. Implementar retry automático para falhas
5. Criar relatório de sincronização
6. Adicionar notificações (email, Slack, etc.)
7. Implementar cache de resultados
8. Criar testes automatizados para os comandos
