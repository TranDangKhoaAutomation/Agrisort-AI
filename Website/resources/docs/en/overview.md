# Overview

AGRISORT-AI is an AI-assisted agricultural grading system with lot management and QR-based traceability.

## Goals
- Standardize post-harvest lot data.
- Enable QR public traceability with configurable publish behavior.
- Provide a complete integration surface for external web/app teams.

## Key measurable metrics
- Pilot accuracy: `93-96%`.
- Target throughput: `1,000-2,000 fruits/hour`.
- Target decision latency: `<= 200 ms`.
- Direct sorting labor reduction: `50-70%`.

## Deployment scope
- Agricultural cooperatives.
- Post-harvest processing stations.
- Small-to-medium agribusiness operators requiring transparent traceability.

## System roles
- `Visitor`: landing, blog, QR trace, contact/consultation forms.
- `Partner`: lot CRUD, CSV import, QR regeneration.
- `Admin`: partner approval, settings, CMS, blog, API keys.

## Public team profile (name + role)
- Nguyen Khac Tung Lam - Team Lead / System Integration.
- Nguyen Dang Quang - AI & Data Pipeline.
- Vo Thi My - Business & Market Analysis.
- Tran Dang Khoa - Embedded & Control.
- Le Huu Trang - Mechanical & Automation.
- Vu Van Dung, B.Eng. - Advisor.
- Nguyen Thi Thanh, M.A. - Advisor.

## Main modules
- Bilingual public website (VI/EN).
- Auth + RBAC.
- Partner dashboard.
- Admin dashboard.
- Machine API (`POST /api/v1/machine/lots`).
- Developer docs portal (`/docs`).

## Technical source of truth
- Route catalog: `resources/docs/route_catalog.php`.
- Section manifest: `resources/docs/manifest.php`.
- OpenAPI runtime: `/docs/openapi.json`.
- Postman runtime: `/docs/postman.json`.

## 2026-02-27 Update: Public trace + supply-chain actors

- Public scanner flow: `GET /trace`, `POST /trace/scan-upload`, `GET /trace/{token}`.
- Supports both lot tokens and package tokens.
- Public users are read-only; they can only view timeline and attachments.
- Timeline items include `event_time`, `stage_code`, `location_name`, `note`, and attachment links.
