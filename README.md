# openclaw-iris

OpenClaw setup for Mercado Veiculos with an editorial orchestrator (`Iris Prime`) and seven Brazilian automotive specialist agents.

## Structure

- `Dockerfile`: OpenClaw runtime image
- `docker-compose.yml`: local Docker service using a bind mount in this repo
- `openclaw-root/`: versioned agent workspaces and templates

## Agents

- `Iris Prime`: editor-in-chief and orchestrator
- `Radar`: market intelligence
- `Vector`: SEO strategy
- `Atlas`: consumer education
- `Navigator`: buyer guidance
- `Torque`: dealer growth
- `Frame`: visual storytelling
- `Sentinel`: quality desk

## Local State

This repository ignores live runtime state, sessions, device pairings, credentials, and tokens.

To bootstrap a fresh environment, copy `openclaw-root/openclaw.json.example` to `openclaw-root/openclaw.json`, fill in the required secrets, and then run:

```bash
docker compose up -d
```
