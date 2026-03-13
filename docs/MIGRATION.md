# Migracao Futura Para VPS

Este documento descreve como mover o `openclaw-iris` para um VPS no futuro sem perder a arquitetura editorial, e com opcao de subir apenas a configuracao versionada ou restaurar tambem o estado operacional.

## O Que Ja Esta No Git

Os itens abaixo ja ficam recuperaveis a partir do repositorio:

- `Dockerfile`
- `docker-compose.yml`
- `README.md`
- `docs/`
- `openclaw-root/openclaw.json.example`
- todos os workspaces versionados em `openclaw-root/workspace*`
- regras editoriais dos agentes: `AGENTS.md`, `SOUL.md`, `MEMORY.md`, `TOOLS.md`, `SOP.md`, `USER.md`

## O Que Nao Vai Para O Git

Por seguranca, estes itens ficam fora do repositorio e precisam ser recriados ou restaurados por backup:

- `openclaw-root/openclaw.json`
- `openclaw-root/devices/`
- `openclaw-root/credentials/`
- `openclaw-root/telegram/`
- `openclaw-root/logs/`
- `openclaw-root/memory/`
- `openclaw-root/agents/`
- pareamentos de painel
- sessoes
- tokens de gateway e canais

## Estrategias De Migracao

Existem dois cenarios recomendados.

### 1. Migracao De Infraestrutura E Configuracao

Use esta opcao quando o objetivo for recriar a arquitetura no VPS, mas sem levar sessoes, pareamentos e credenciais antigas.

Voce vai recuperar:

- todos os agentes
- a malha editorial
- os documentos de operacao
- o `compose`
- o `openclaw.json.example`

Voce vai recriar manualmente:

- `openclaw-root/openclaw.json`
- `gateway token`
- `Telegram bot token`
- devices do painel
- pareamentos e sessoes

### 2. Migracao Completa De Estado

Use esta opcao quando o objetivo for manter tambem:

- token real do gateway
- configuracao real de canais
- devices pareados
- memoria local
- parte do estado operacional

Esta opcao exige backup seguro fora do Git.

## Pre-Requisitos No VPS

- Ubuntu 22.04 ou equivalente
- `git`
- `docker`
- `docker compose`
- acesso SSH ao repositorio
- porta `18789` liberada se o painel/gateway for exposto

## Estrutura Esperada

O projeto foi preparado para usar bind mount local:

```yaml
./openclaw-root:/root/.openclaw
```

Isso significa que, no VPS, o estado do OpenClaw continua vivendo dentro da pasta do projeto.

## Fluxo Recomendado: Rebuild Limpo No VPS

### 1. Clonar O Repositorio

```bash
git clone git@github.com:williamduartebr/openclaw-iris.git
cd openclaw-iris
```

### 2. Criar A Configuracao Real

```bash
cp openclaw-root/openclaw.json.example openclaw-root/openclaw.json
```

Depois, editar `openclaw-root/openclaw.json` e preencher:

- `channels.telegram.botToken`
- `gateway.auth.token`
- qualquer outro segredo futuro

### 3. Subir O Servico

```bash
docker compose up -d --build
```

### 4. Validar

Comandos uteis:

```bash
docker compose ps
docker compose logs --tail=100 openclaw
docker compose exec -T openclaw bash -lc 'openclaw agents list --json'
docker compose exec -T openclaw bash -lc 'openclaw health'
```

### 5. Reparear O Painel

Como `devices/` nao vai no Git, o painel precisara ser pareado novamente.

Fluxo esperado:

1. abrir a URL do painel com o token do gateway
2. aprovar o device novo
3. validar acesso

### 6. Reconfigurar Telegram Se Necessario

Se a migracao for limpa, confirme:

- token do bot
- politicas de grupo/DM
- comandos
- atualizacoes do provider

## Fluxo Alternativo: Restaurar Estado Completo

Se voce quiser levar o estado real atual, gere um pacote privado fora do Git na maquina de origem.

### Backup Na Maquina De Origem

Exemplo:

```bash
tar -czf openclaw-runtime-backup.tgz \
  openclaw-root/openclaw.json \
  openclaw-root/devices \
  openclaw-root/credentials \
  openclaw-root/telegram \
  openclaw-root/memory \
  openclaw-root/agents
```

Este arquivo contem segredos. Nao envie para repositorio publico.

### Restore No VPS

Depois de clonar o repo:

```bash
tar -xzf openclaw-runtime-backup.tgz
docker compose up -d --force-recreate
```

## Checklist De Cutover

Antes da virada:

- repo clonado no VPS
- `openclaw-root/openclaw.json` criado
- segredos configurados
- porta `18789` liberada conforme necessidade
- painel testado
- Telegram testado
- lista de agentes validada
- logs sem erro critico de boot

## Checklist Pos-Migracao

- `Iris Prime` aparece como agente principal
- `Radar`, `Vector`, `Atlas`, `Navigator`, `Torque`, `Frame` e `Sentinel` aparecem na lista
- o painel abre
- o gateway responde
- o bot Telegram conecta
- um teste simples de orquestracao entre agentes funciona

## Riscos Conhecidos

### 1. Estado Sensivel Nao Versionado

Se voce esquecer de recriar ou restaurar `openclaw-root/openclaw.json`, o servico sobe sem os segredos reais.

### 2. Device Pairing

Mesmo com a configuracao correta, o painel pode exigir novo pareamento.

### 3. Token Antigo Em Cache

Se o navegador guardar um token antigo, o painel pode falhar ate limpar os dados do site ou abrir em aba anonima.

### 4. Diferenca De IP Ou Origin

Se o VPS usar dominio ou IP publico diferente, revise `gateway.controlUi.allowedOrigins` no `openclaw.json`.

## Recomendacao Pratica

Para um VPS novo, o caminho mais limpo e seguro costuma ser:

1. clonar o repo
2. copiar o `.example`
3. preencher segredos
4. subir o servico
5. reparar devices
6. validar canais

Para continuidade total de operacao, faca tambem backup privado do runtime sensivel.
