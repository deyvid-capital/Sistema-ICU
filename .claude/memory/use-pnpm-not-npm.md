---
name: use-pnpm-not-npm
description: "Always use pnpm for this project, never npm"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 2b326268-c25c-40b3-801d-7eaa65ef9341
  modified: 2026-07-23T13:49:53.653Z
---

For the Sistema-ICU project, always use `pnpm` for Node package management and scripts (e.g. `pnpm run build`, `pnpm run dev`, `pnpm install`). Never use `npm`.

**Why:** The user explicitly instructed this and the project standardizes on pnpm.

**How to apply:** Replace any `npm ...` command with `pnpm ...`. Note the CLAUDE.md examples show `vendor/bin/sail npm run build`, but the user overrides this — prefer `pnpm run build` (confirm whether it runs through Sail or directly if unsure).
