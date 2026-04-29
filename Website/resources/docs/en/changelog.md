# Changelog

## Docs v1 (2026-02-26)
- Added public docs portal at `/docs`.
- Added single source route catalog for route matrix, OpenAPI, and Postman generation.
- Added bilingual markdown docs for all system modules.
- Added `tests/docs_consistency.php` to detect route/docs drift.

## Update rules
- Any endpoint change must update `route_catalog.php` first.
- Any feature change must update related docs section(s).
- Increase docs version when changing major contracts.


