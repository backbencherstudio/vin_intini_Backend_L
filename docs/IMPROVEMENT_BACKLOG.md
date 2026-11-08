# Prioritized improvement backlog

Date: 2026-11-08. Tasks are proposed, not completed. IDs refer to [the audit](PROJECT_CODE_AUDIT.md). Use [the architecture proposal](ARCHITECTURE_IMPROVEMENT_PLAN.md) as the implementation guide and [verification results](AUDIT_VERIFICATION.md) as the baseline.

## Phase 1 — Close authentication and payment bypasses

These tasks precede broad refactoring. Coordinate affected mobile/web contracts where a new challenge token becomes mandatory.

| Priority | Task | Completion evidence |
| --- | --- | --- |
| P0 | A01: stop token issuance for already-verified registration | Arbitrary OTP for verified/suspended accounts returns no credentials |
| P0 | A02: require proof for restore and enforce deadline/status | Email-only, expired, reused, and cross-account attempts fail |
| P0 | A03: bind reset authorization to a single-use caller-held proof | A second caller cannot consume another caller's verified state |
| P0 | A04: enforce revocation across JWT transports | Header/query/body requests all reject revoked tokens |
| P0 | B01: verify mobile purchases server-side | Forged/cross-user/refunded/environment-mismatched transactions fail |
| P1 | A05–A07: bind 2FA to first-factor challenge; enforce rate limits and OAuth state | Proof/attempt/replay matrix passes |
| P1 | A08–A10: restrict previews, escape/sanitize HTML, remove unsafe maintenance/GET deletes | Production guest route matrix and security tests pass |

Suggested ownership: backend authentication owner for A01–A07, billing owner for B01, web/backend owner for previews and HTML. These are responsibilities, not assignments to specific people.

## Phase 2 — Make payment and lifecycle behavior reliable

| Task | Dependency | Completion evidence |
| --- | --- | --- |
| B02: allow payment application only once from pending state | Verified purchase boundary | Confirming paused/completed/cancelled campaigns cannot grant time |
| B03: webhook and client confirmation share an activation operation | B02 | Client disconnect after 3DS still yields one activation |
| B04: persist idempotent purchase attempts and reconciliation | Price snapshot/attempt model decision | Timeout/retry and post-charge DB failure never double-charge |
| B05: centralize product boost projection | Agreed overlap/extension semantics | Multiple campaign pause/resume/expiry scenarios preserve entitlement |
| B06: central entitlement evaluation | Agreed grace/cancellation policy | Before/at/after-period tests pass |
| B07: enforce target package and taxonomy compatibility | Request/action boundary | Cross-section/network/package writes fail |

Before adding unique constraints, inspect schema and existing duplicates with Boost read-only tools. Apply corrective migrations rather than editing already-deployed migration history. Rehearse provider event ordering and duplicate delivery using fixtures; do not test real charges against production.

## Phase 3 — Repair runtime and verification failures

- [ ] C01: optional product URL omitted/null succeeds according to the API contract.
- [ ] C02: all reply failure paths close transactions and clean staged files.
- [ ] C03: missing campaign targets return a controlled result; tracking eligibility is explicit.
- [ ] C04: image replacement survives encoding/storage/database failures.
- [ ] C05: credential rotation has a documented worker refresh/reload policy and same-worker tests.
- [ ] C06: configure and bound video processing; introduce async media state if adopted.
- [ ] D01: restore the production asset build and visually inspect affected pages.
- [ ] D02: fix shared inferred response types first, then work through endpoint diagnostics in batches.
- [ ] Triage every failing test from the baseline into an application bug, stale contract expectation, fixture defect, or environment dependency. Do not weaken assertions merely to make the suite green.

## Phase 4 — Extract one domain at a time

Suggested order after urgent fixes:

1. Authentication challenges/session issuance, with shared account-status decisions.
2. Advertising payment activation and campaign transitions.
3. Product creation/update and file ownership.
4. Company posts/comments/likes and visibility policies.
5. Groups, invitations, connections, and follows.
6. Jobs, applications, recommendation queries, and analytics.
7. Personal/industry messaging and notifications.
8. Admin web/API operation reuse, then domain route organization.

For every slice: inventory routes → characterize valid behavior → define policy/invariant → extract Request/Action/Resource or Query → run focused tests → verify API output → remove only obsolete implementation code. Preserve tests; any test deletion needs explicit approval. Do not rename all routes and change all envelopes during extraction.

## Required regression matrix

| Area | Cases that must be represented |
| --- | --- |
| Authentication | Guest, unverified, verified, suspended, soft-deleted, expired restoration window, revoked session |
| Challenges | Wrong account/purpose/client, expired, consumed, attempts exhausted, concurrent reuse |
| Authorization | Owner, unrelated user, relevant group role, company actor, admin; read and write separately |
| Post visibility | Public, connections-only, group-only, followers-only where supported; list/detail/comment/reply/like endpoints agree |
| Payments | Valid, invalid signature/proof, wrong amount/currency/product/user/environment, duplicate, delayed, out-of-order, refund |
| Campaigns | Pending, active, paused, completed, cancelled; two campaigns on one product; deleted target |
| Entitlements | Before/at/after expiry, trial, cancellation at period end, allowed grace, stale webhook |
| Files | Valid replacement, invalid input, write failure, DB failure, rollback cleanup, conversion retry |
| Lists | Zero/one/many records, stable order, next page, maximum limit, query count, no visibility leakage |
| Effects | Only after successful commit; retries do not duplicate externally observable outcomes |
| Workers | Same worker with different users and changed integration settings |

Use existing factories and feature tests first. Freeze time for expiry, fake storage/mail/notifications, and fake exact external endpoints or integration boundaries. Add pure unit tests only for isolated calculations or state decisions. HTTP tests must exercise real middleware for security fixes; `actingAs` alone cannot prove JWT parsing/revocation safety.

## Verification and release gates

- Run the narrowest affected test files first; after the baseline is repaired, run the full suite in a disposable MySQL database matching production behavior.
- Use `php -d memory_limit=512M vendor/bin/pest` when the default CLI runner cannot complete; set the memory budget explicitly in CI rather than relying on a parent process option.
- Run `vendor/bin/pint --dirty --format agent` for PHP changes.
- Run `npm run build` for asset changes.
- Run scoped Scramble export for changed contracts; finish shared-type work with `php -d memory_limit=512M artisan scramble:analyze --fail-on-empty --fail-on-unknown`.
- Review effective route middleware, not only route source grouping. Ensure preview/maintenance endpoints are unavailable in production.
- Rehearse migrations and worker reloads on staging. Monitor payment reconciliation and failed jobs after rollout.

No new dependency is necessary for the basic architecture. Consider tools such as static analysis or an HTML sanitizer only with explicit dependency approval and a concrete use case. The current review does not authorize a schema rewrite, provider reconfiguration, or deployment.

## Definition of done for an improvement PR

The PR explains the old failure and new behavior, includes a regression test where meaningful, preserves unrelated contracts, leaves ownership/transaction boundaries clear, and records relevant verification results. It does not merely move a large controller method into a large service method.
