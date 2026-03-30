## Project Overview

HyperPress-Core is the Composer library that contains the HyperPress runtime.

It owns:
- API routing and rendering
- Asset loading for HTMX, Alpine, Datastar
- Admin options and compatibility logic
- Block integration and HyperFields/HyperBlocks wiring

## Scope Rules

- Keep `api-for-htmx` as a thin WordPress plugin adapter.
- Put runtime logic changes in this package (`HyperPress-Core`).
- Preserve backwards compatibility for public hooks, constants, and helper functions.
