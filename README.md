# openclaw-iris

OpenClaw setup for Mercado Veiculos with an editorial orchestrator (`Iris Prime`) and seven Brazilian automotive specialist agents.

## Structure

- `Dockerfile`: OpenClaw runtime image
- `docker-compose.yml`: local Docker service using a bind mount in this repo
- `docs/`: migration and agent operation documentation
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

To bootstrap a fresh environment:

1. Copy `.env.openclaw.example` to `.env.openclaw`
2. Copy `openclaw-root/openclaw.json.example` to `openclaw-root/openclaw.json`
3. Fill in the required secrets and tokens
4. Start the stack

The example config uses NVIDIA's OpenAI-compatible endpoint with `nvidia/z-ai/glm-5.1` as the default agent model.

```bash
docker compose up -d
```

## Docs

- `docs/MIGRATION.md`: future VPS migration and restore guide
- `docs/AGENTS.md`: what each agent does, when it enters, and which categories it owns best
- `docs/API.md`: article delivery contract for the local publishing API
