# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Laravel 12 (REST API), Vue 3 + Vite + Tailwind 4, MySQL 8 (Docker Compose locally, MySQL on deploy). Legacy: vanilla PHP 8 + jQuery admin pages on the same database.

## Users

**Primary:** Internal operations staff (L2/support/engineering) at a fictional Australian cloud-voice provider (**ConnectWave**). They triage partner and customer tickets, update status with audit notes, and cross-check legacy tooling.

**Secondary:** Job reviewers evaluating full-stack PHP skills (portfolio viewer, not a production operator).

## Product Purpose

Provide a credible **operations desk** for hosted UC work: **corporate pricing quotes**, **provisioning orders**, faults, number ports, reseller and customer context, and searchable ticket volume. Success means reviewers can complete a realistic task flow (scan bids → open quote → see linked provisioning, or find ticket → read history → change status with note) in under two minutes.

## Positioning

A **single-database** story across modern Vue, Laravel JSON API, and legacy PHP/jQuery—aligned with telco portal job themes without impersonating any real carrier.

## Operating Context

- AU business hours assumed for sample data and copy tone.
- Demo runs on local Herd/Vite or shared PHP hosting with built assets.
- All customer/account names are **funny parodies** of well-known AU brands (first letter ≠ real brand). Sample **NSW suburb** labels on service lines. Fiction is stated on **`/portfolio`**, not repeated in the desk chrome.

## Capabilities and Constraints

- Dashboard aggregates, paginated queue, **corporate bids** and **provisioning** lists, quote detail in new tab, ticket detail in new tab.
- Status changes require a written note; history is immutable audit log.
- No authentication (portfolio scope); do not imply production security.
- Do not name or mimic a specific real telco employer in UI or public README.

## Brand Commitments

- Product name **ConnectWave** is fictional.
- Honest fiction: **`/portfolio`** explains sample data; desk UI stays operator-focused.
- Operate mode: scanability over marketing flair; dark desk UI for primary app.

## Evidence on Hand

- Runnable demo: `demos/uc-service-desk/`
- Recruiter review guide: **`/portfolio`** on the deployed app
- No real customer testimonials or production metrics—do not fabricate.

## Product Principles

1. **Task first:** queue and ticket detail beat decorative chrome.
2. **Audit trail:** status history with notes is always visible on detail.
3. **Honest fiction:** brand and data are clearly sample, not stealth research.
4. **Stack proof:** show Laravel + Vue + legacy PHP on one DB.
5. **Accessible ops:** keyboard focus, announced errors, usable on narrow viewports.

## Accessibility & Inclusion

Target **WCAG 2.2 AA** for portfolio demo surfaces: visible focus, status messages, sufficient contrast on primary text, touch-friendly controls on mobile web.
