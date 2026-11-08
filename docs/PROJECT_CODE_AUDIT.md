# Project code and logic audit

Review date: 2026-11-08. This is an assessment of the current working tree, not an implementation of fixes.

Read alongside [architecture proposal](ARCHITECTURE_IMPROVEMENT_PLAN.md), [implementation backlog](IMPROVEMENT_BACKLOG.md), and [verification results](AUDIT_VERIFICATION.md).

## Executive assessment

There are serious authentication and payment defects that should be fixed before a broad controller refactor. Moving the same logic into services will preserve those defects unless the business rules and regression tests change too.

The application is not entirely controller-based: it already has 18 service classes, 13 Form Requests, 8 API Resources, enums, jobs, and model behaviors. However, 73 controllers contain 28,465 lines, and there are no application Policy classes. Authorization, response formatting, queries, and workflows are repeated across controllers.

Installed versions checked with Composer: Laravel 13.40.0, Octane 2.23.0, Scramble 0.13.53, Socialite 5.31.0, JWT Auth 2.3.1, Pest 4.7.8, Stripe PHP 21.3.3. Configured Octane driver: FrankenPHP.

## Scope and evidence

The review inventories the project, syntax-checks all 556 PHP files in app/routes/config/bootstrap/database/tests (excluding bootstrap cache), inspects registered routes, runs the existing suite and build, and analyzes the full selected API with Scramble. Manual review concentrates on authentication, authorization, billing, advertisements, social content, files, jobs, configuration, and their tests. Automated scans also cover the remaining first-party source.

This is not a claim that every line or every possible execution path has been proven correct. Vendor source was inspected only where needed to verify framework behavior. Generated assets, binary uploads, external provider dashboards, production infrastructure, real payment transactions, browser execution, and concurrent load were not independently validated. No production exploit requests were sent. Findings marked “code-confirmed” follow directly from the implementation; their proposed regression tests have not been added in this documentation-only change.

Severity: **P0** immediate authentication/payment integrity issue; **P1** significant security, correctness, or build failure; **P2** reliability, maintainability, or scaling issue. Severity assumes these routes are deployed as registered; an external gateway may reduce exposure but does not fix the application contract.

## Authentication and security

### A01 — P0: registration verification issues tokens without verifying the OTP

**Code-confirmed.** `app/Http/Controllers/Api/AuthController.php`, `verifyRegisterOtp()` around line 450; `routes/api.php`.

When `is_verified` is already true, the method calls `auth('api')->login($user)` before inspecting any OTP record. The public endpoint only requires a syntactically valid four-digit value. It also skips the normal login checks for suspension and two-factor authentication.

**Impact:** knowing a verified user's email is sufficient to obtain a signed JWT. Missing session bookkeeping is not a security boundary; A04 makes non-header JWT requests especially dangerous.

**Fix:** return an “already verified” result without credentials. Only issue tokens after a valid authentication flow, using a shared session issuance operation with account-status checks.

**Regression:** already-verified users never receive a token for an arbitrary OTP; suspended users never receive credentials; valid first verification consumes the challenge once.

### A02 — P0: account restoration needs only an email address

**Code-confirmed.** `app/Http/Controllers/Api/SecuritySettingsController.php:705`, `restore()`; public `POST api/account/restore`.

Password validation and password checking are commented out. The method restores a soft-deleted user, removes deletion records, and returns a JWT with an active login record based only on the supplied email. It does not enforce the restoration deadline or suspension policy.

**Fix:** require a short-lived, single-use restoration proof earned through verified credentials or verified recovery. Enforce the allowed restoration window and account status, and make restoration/session creation transactional. Define recovery for social-only users explicitly.

**Regression:** email-only requests fail; expired or reused proof fails; a valid proof restores only its own account; suspension remains effective.

### A03 — P0: password reset trusts shared verified state rather than caller proof

**Code-confirmed.** `app/Http/Controllers/Api/ForgotPasswordController.php:104`, `resetPassword()`; `tests/Feature/ForgotPasswordFlowTest.php`.

Verification sets `verified_at` on an account's OTP row. The subsequent public reset accepts only email and password. During that verified window, another caller can reset the password without the OTP or a reset token. The existing happy-path test explicitly approves this unsafe contract.

**Fix:** exchange a valid OTP for a random, purpose-bound, expiring reset token, store its hash, and atomically consume it when updating the password and revoking sessions. Alternatively require and atomically consume the OTP in the reset request. Do not use account-wide `verified_at` as authorization.

**Regression:** caller B cannot reset caller A's account after A verifies; token reuse, wrong-user tokens, expiration, and concurrent consumption fail.

### A04 — P0: session revocation is bypassed for query/body JWTs

**Code-confirmed against installed package source.** `app/Http/Middleware/CheckActiveSession.php:15`; `vendor/tymon/jwt-auth/src/Providers/AbstractServiceProvider.php`, `registerTokenParser()`.

The middleware immediately continues when API authentication succeeds but `bearerToken()` is absent. JWT Auth's registered parser accepts both query-string and input-source tokens as well as the Authorization header. Consequently a valid token whose login activity has been revoked can bypass this check by changing transport. Suspension and password-reset session revocations rely on this middleware.

**Fix:** enforce the same session and account-status checks for every supported token transport. Prefer a documented header-only policy if clients permit it. Keep any test-only bypass out of production middleware.

**Regression:** a revoked token fails in the header, query, and body; missing activity fails; suspended users fail; ordinary valid bearer requests succeed.

### A05 — P1: two-factor login is not bound to a completed first factor

**Code-confirmed.** `AuthController::login()` and `TwoFactorController::verifyLogin()` at line 102.

Password login returns an email and a two-factor-required flag but creates no verifiable login challenge. The public verification endpoint accepts email plus TOTP/recovery code and issues a JWT independently. It also omits the normal suspension check. This makes the advertised two-factor flow a standalone second-factor login.

**Fix:** create a short-lived login challenge after validating the password; require it for the second step, enforce account status again, and consume both challenge and recovery code atomically. Decide whether social login must also honor enrolled two-factor authentication; `SocialController::processSocialUser()` currently issues tokens directly.

**Regression:** a valid TOTP without a first-factor challenge fails; challenges cannot cross accounts or be reused; suspended users fail even with a valid code.

### A06 — P1: short OTP endpoints lack application throttling

**Code-confirmed.** Public `send-otp`, `verify-otp`, `password-reset`, and `register/verify-otp` routes in `routes/api.php`; `ForgotPasswordController`; `AuthController`.

Unlike login and two-factor endpoints, these registered routes have no throttle middleware. Password-reset OTPs have only four digits and no failed-attempt counter. Sending new reset OTPs also has no controller cooldown.

**Fix:** enforce both account/challenge and IP limits, an attempt budget, resend cooldown, and generic account-discovery responses where appropriate. Generate codes with `random_int`, and avoid plaintext OTP storage where practical. Rate limiting complements A01–A03; it does not repair them.

**Regression:** repeated wrong attempts invalidate or lock the challenge, resend requests are bounded, and limits cannot be bypassed by changing only IP or email casing.

### A07 — P1: OAuth callback accepts missing or invalid state

**Code-confirmed.** `app/Http/Controllers/Api/SocialController.php:66`, `callback()`.

The redirect stores a random cache key, but callback uses `Cache::pull(..., [])` and proceeds with defaults when the key is absent or expired. `stateless()` disables Socialite's normal state verification. The custom state mechanism therefore does not reject invalid callbacks.

**Fix:** require a valid, single-use state record bound to the initiating client and provider. Use a browser session binding or an appropriate native-app PKCE/challenge flow. Reject invalid state before exchanging provider credentials. Also read the optional Apple `nonce` using `?? null`; it is currently accessed directly even though validation allows omission.

**Regression:** absent, expired, reused, wrong-provider, and wrong-client state fail; Apple token login without an optional nonce does not produce an undefined-key error.

### A08 — P1: public previews expose content without visibility filtering

**Code-confirmed.** `routes/web.php:38`, `routes/boosting_test.php:34`.

Developer previews are included whenever the file exists. The public newsfeed preview queries posts without applying their public/connections/groups/followers visibility rules. It can also select a real database user as a preview identity. Product preview GET requests update expiry flags.

**Fix:** disable developer previews outside local/test environments or protect them with explicit admin authorization; use fixtures, apply real visibility rules, and remove state changes from preview GET handlers.

**Regression:** production guests cannot reach previews; restricted posts never appear in public output; viewing a preview cannot mutate production records.

### A09 — P1: preview HTML handling permits stored script execution

**Code-confirmed unsafe sink; no browser exploit executed.** `resources/views/partials/newsfeed-post-items.blade.php:253`.

`strip_tags` retains allowed elements and their attributes. The regex removes only style/id/dir/class attributes with double quotes; event handlers and unsafe link schemes remain. The result is printed with `{!! $safeHtml !!}`. Stored post descriptions can reach this preview.

**Fix:** render escaped plain text, or use a deliberately chosen HTML sanitizer with element, attribute, and URL-scheme allowlists. Dependency additions require separate approval. Do not maintain a regex-based HTML sanitizer.

**Regression:** event-handler attributes and unsafe schemes are removed or escaped, while permitted formatting still renders. Check other HTML sinks independently; their existence alone does not prove XSS.

### A10 — P1: maintenance and destructive operations use unsafe routes

**Code-confirmed.** `routes/web.php:42` exposes unauthenticated `GET /clear` calling `optimize:clear`. The same file registers GET delete routes for partners, sections, categories, biotech, pharma, and publications.

**Impact:** anyone can request cache clearing. Authenticated destructive GET routes can be triggered by navigation and do not receive normal CSRF protection for unsafe methods.

**Fix:** perform maintenance through deployment/CLI operations. Convert deletion to DELETE or protected POST, retain authorization, and require CSRF verification on web requests.

**Regression:** guest maintenance calls fail; GET cannot delete; protected deletion requires authorization and CSRF.

### A11 — P2: login tokens are placed in frontend URLs

**Code-confirmed.** `SocialController::processSocialUser()` around line 294.

The web redirect includes a base64-encoded payload containing the JWT in `?auth=...`. Base64 is reversible. URLs can be retained in browser history, access logs, analytics, and referrer flows depending on configuration.

**Fix:** redirect with a short-lived, single-use exchange code bound to the initiating client, then exchange it through the intended secure session/token flow. Redact credentials from logs.

**Regression:** redirect URLs contain no usable access token; exchange codes expire and cannot be reused by another client.

## Payments and business rules

### B01 — P0: mobile boost activation accepts an unverified transaction ID

**Code-confirmed.** `ProductBoostController::purchase()` and `app/Services/ProductBoostPaymentService.php:282`, `activateRevenueCatBoost()`.

The client chooses a package and supplies a transaction ID. The service checks package status and local duplicates, then creates an active paid campaign and successful transaction. It never verifies the purchase with RevenueCat/store data. The duplicate branch returns an existing campaign without checking its user/product ownership.

**Fix:** activate only from authenticated provider evidence or a server-side verified purchase. Match app user, store product, environment, transaction, purchase state, and package; reject refunds/revocations and cross-account replay. Scope idempotent responses to the rightful purchaser and target.

**Regression:** invented, wrong-user, wrong-product, sandbox-in-production, refunded, and reused transactions cannot grant credit. A valid replay returns the original owned result without another credit.

### B02 — P1: confirming an old successful payment can grant another duration

**Code-confirmed.** `ProductBoostPaymentService::confirmStripePayment()` around line 192.

Only ACTIVE is treated as already processed. A paused or completed campaign still has a succeeded Stripe payment intent. Confirming that intent again computes a new end date and changes it back to ACTIVE. This can bypass the pause policy or reactivate an expired paid entitlement.

**Fix:** allow payment activation only for a pending, unapplied payment. Keep payment application separate from campaign lifecycle; terminal states must not be reset by confirmation. Lock and reload the relevant rows inside the transaction.

**Regression:** repeated confirmation of active, paused, completed, and cancelled campaigns never adds duration or changes lifecycle state; concurrent confirmation applies credit once.

### B03 — P1: successful 3DS payments depend on the client returning

**Code-confirmed missing recovery path.** `StripeWebhookController::handle()`; `ProductBoostPaymentService::chargeStripeBoost()` and `confirmStripePayment()`.

The 3DS path creates a pending campaign and relies on the client confirmation endpoint. The Stripe webhook switch does not handle `payment_intent.succeeded` for product boosts. If the app closes after the provider succeeds, the local campaign can remain pending indefinitely.

**Fix:** route authenticated success events to the same idempotent payment-application operation used by client confirmation; add reconciliation for missed events. Verify the payment's target, amount, currency, and owner using the stored purchase record.

**Regression:** payment succeeds after the client disconnects and still activates exactly once; webhook-before-confirm and confirm-before-webhook produce the same result.

### B04 — P1: retries can create duplicate external payments

**Code-confirmed missing safeguards; no live charge performed.** `StripeService::createPaymentIntent()` around line 198; `ProductBoostPaymentService::chargeStripeBoost()`.

Stripe intent creation has no stable idempotency request option. It happens before the local campaign transaction. A timeout or a local DB failure after provider success can make a retry charge again. Local transactions cannot roll back an external charge. Boost updates also use models read before transactions without locking, allowing overlapping purchases to overwrite duration calculations.

**Fix:** persist a purchase attempt with an idempotency key and immutable price/duration snapshot; reuse it for retries. Keep network operations outside long DB locks, then apply the verified result under row locks and unique constraints. Reconcile partially completed attempts.

**Regression:** provider success followed by timeout or DB failure is recoverable with one charge; two distinct concurrent purchases preserve both purchased durations; duplicate calls do not duplicate mail or transaction rows.

### B05 — P1: pausing one campaign disables a product with other active campaigns

**Code-confirmed.** `app/Models/AdCampaign.php`, `pause()` and `resume()`; `CleanExpiredBoosts::handle()`.

Pause always sets the target's `is_boosted` to false, even if another paid campaign is still active. Resume overwrites `boost_expires_at` with that campaign's date. The cleanup command already checks for other active campaigns, so the invariant differs across entry points. Feed badge/order behavior can disagree with ad selection.

**Fix:** define one projection from eligible campaigns to product boost flags, effective expiry, and score. Recalculate it transactionally after every activation, pause, resume, expiry, and cancellation. Define whether purchases overlap or extend a single entitlement.

**Regression:** two campaigns on one product remain correctly boosted when one pauses/expires; resuming an earlier campaign does not shorten another entitlement.

### B06 — P1: active subscription checks ignore time boundaries

**Code-confirmed; impact depends on provider synchronization.** `app/Models/Subscription.php`, `isActive()`; `app/Models/User.php`, `hasActiveSubscription()`; `IndustryProductController::validateIndustryAccess()`.

Both checks rely on status alone. An active row with a past period end continues to grant capabilities when a webhook is delayed or missing. Other parts of billing have date-hydration behavior, but these access checks do not consistently invoke it.

**Fix:** centralize entitlement decisions with explicit expiration, cancellation, trial, billing-grace, and reconciliation policies. Avoid making every authorization check call the provider.

**Regression:** freeze time before/at/after expiry; verify allowed grace periods and cancelled-at-period-end behavior; stale active rows cannot grant indefinite access.

### B07 — P2: package eligibility and product taxonomy are not validated together

**Code-confirmed.** `ProductBoostPaymentService::resolvePricingAndDuration()` and `activateRevenueCatBoost()` only require an active package, while package listing filters `target_entity`. `StoreIndustryProductRequest`, `UpdateIndustryProductRequest`, and `IndustryProductController::store()/update()` validate section/category existence independently.

**Impact:** a product purchase can submit a package intended for another entity. Product writes can combine a category from another section or a section from another network/type, making feed filters inconsistent.

**Fix:** enforce product/all package eligibility at the write boundary; validate category → section → network/type relationships as one invariant, including partial updates against the existing record.

**Regression:** mismatched combinations fail with 422; a valid partial update retains compatible existing taxonomy.

## Runtime correctness and reliability

### C01 — P1: valid product creation can fail when optional URL is omitted

**Code-confirmed.** `StoreIndustryProductRequest::rules()` makes `product_url` nullable, but `IndustryProductController::store()` around line 641 uses `$validated['product_url']` directly.

Omitted optional keys are not guaranteed to exist in validated input. Access produces an undefined-array-key error instead of creating the product. Image storage happens before this point, so failures can also leave unused files.

**Fix:** use a null default or intentionally require the field; preserve the chosen public contract. Clean up newly stored files if persistence fails.

**Regression:** create a product with the optional URL omitted and with explicit null; both follow the documented behavior and leave no orphan on failure.

### C02 — P1: comment reply error returns with an open transaction

**Code-confirmed.** `app/Http/Controllers/Api/CommentController.php:85–126`.

The method begins a transaction, may store an image, then returns 404 if the parent comment author is missing/deleted without committing or rolling back. A mismatched parent/post lookup also becomes a generic 500 through the broad catch. Under persistent workers, connection state makes unbalanced transactions especially risky, even where framework cleanup mitigates later requests.

**Fix:** perform preconditions before opening a transaction; use `DB::transaction()` for the write operation and typed exceptions for expected failures. Compensate uploaded files on failure.

**Regression:** deleted-parent and wrong-post replies return the intended 404/422, preserve data/counters, leave no file, and leave the DB transaction level unchanged.

### C03 — P1: ad clicks can crash for a deleted target

**Code-confirmed.** `ProductBoostController::recordClick()` around line 754.

Campaign lookup can succeed while its polymorphic target no longer exists. `method_exists($campaign->boostable, ...)` receives null and throws a TypeError. Polymorphic target columns do not provide a normal foreign key cascade to the product table.

**Fix:** explicitly handle missing/ineligible targets before destination resolution and tracking; define whether such campaigns are cancelled or retained for history. Also reject or explicitly classify tracking against expired/paused campaigns; direct ID lookup currently does not enforce active eligibility.

**Regression:** deleted, expired, and paused targets produce a stable response and do not inflate active campaign metrics.

### C04 — P2: image replacement deletes the old file before success

**Code-confirmed.** `app/Services/ProfileImageService.php:17`, `storeUploaded()`.

If optimization or writing the new file fails, the old image has already been deleted and the user's stored path can still reference it.

**Fix:** store the replacement first, persist its reference, then schedule old-file deletion after commit. Delete the newly created file if persistence fails.

**Regression:** simulated image-write and DB failures preserve the original file/reference; successful replacement removes only the old file.

### C05 — P2: long-running workers retain old integration credentials

**Code-confirmed lifecycle risk; not reproduced on a running worker.** `AppServiceProvider::boot()/register()`, `IntegrationSettingsService::set()/applyOverrides()`, `RevenueCatService::__construct()`, `Admin/Api/IntegrationSettingController::update()`.

Overrides are applied at application boot. Updating a setting clears its cache but does not reconfigure already-running workers. RevenueCat is also a singleton that copies credentials at construction. FrankenPHP/queue workers can continue using old credentials after an admin reports a successful update.

**Fix:** define a settings version and safe refresh/client reconstruction strategy, or require an operational worker reload after credential changes. Scope request-derived services; changing this singleton alone does not solve boot-only config refresh.

**Regression:** two requests/jobs on the same worker separated by a settings change use the documented configuration version; no user's state leaks into another request.

### C06 — P2: synchronous video processing and environment lookup are deployment hazards

**Code-confirmed design risk.** `MediaUploadService::processVideo()` and `IndustryMediaUploadService::processVideo()`.

Video transcoding uses blocking `exec` in the request. The industry service reads `env('FFMPEG_BINARY')` directly and defaults to a Windows executable path. With cached configuration, a value only in `.env` is not reliably available here. Long videos can exhaust request/worker time budgets.

**Fix:** read a configured executable, run a bounded process, and move expensive transcoding to a retryable media job with explicit pending/ready/failed state. Keep the original until successful conversion and clean partial outputs.

**Regression:** missing executable, timeout, invalid input, job retry, and successful conversion all leave correct records/files. Verify Linux/macOS deployment configuration separately.

## Build, documentation, performance, and maintainability

### D01 — P1: frontend production build fails

**Executed failure.** `npm run build` fails because `postcss.config.js` registers `tailwindcss` directly as a PostCSS plugin while the installed major is v4. `vite.config.js` does not use the already-declared `@tailwindcss/vite` plugin, and the stylesheet retains the older directives.

**Fix:** choose one supported v4 integration, preferably the already available Vite integration; migrate the CSS entry and check legacy theme/forms configuration. Do not install another integration unnecessarily. Rebuild and visually check actual pages.

### D02 — P1: strict generated API documentation is incomplete

**Executed failure.** Scramble matched 339 routes/operations and reported **475 errors and 4 warnings** with 512 MB memory. The default 128 MB run exhausted memory. These are documentation inference diagnostics, not 475 proven runtime bugs.

Examples include unknown auth response types, untyped validation inputs, dynamic response arrays, and aggregate fields in `PlanResource`. Fix shared resources/types first, then endpoints. Keep inference honest; do not add speculative annotations simply to silence diagnostics. Run scoped exports and compare actual response contracts.

### D03 — P2: ad selection performs work proportional to all eligible campaigns

**Code-confirmed query pattern; no production benchmark.** `Advertising/FairPlayAdRotationService::resolveAdForFeed()` fetches the candidate set with `get()` and executes an impression count for each candidate in its filter.

**Fix:** bound/select candidates in SQL and batch daily impression counts. Use measured query plans and representative data before proposing indexes. Make delivery/frequency-cap decisions safe under concurrent requests.

**Regression:** compare eligibility and rotation results plus query counts for small and larger candidate sets; verify caps across simultaneous requests.

### D04 — P2: response serialization can trigger hidden queries

**Code-confirmed conditional behavior.** `IndustryProductResource::toArray()` queries likes when no preloaded map is supplied, and queries campaign fallback data. `HasBoostableCampaigns::getBoostStatus()` can query the same fallback again. `ProductBoostController::myCampaigns()` loads all matching campaigns to compute totals and accepts an uncapped `per_page`.

**Fix:** have query services supply all relationships/counts/flags, including the latest campaign; serialize without queries. Calculate dashboard totals in SQL and cap pagination centrally.

**Regression:** list query counts do not grow per item; aggregates match known fixtures; negative/excessive page sizes are rejected or normalized.

### D05 — P2: exceptions, effects, and API shapes vary by controller

**Code-confirmed pattern.** Examples: `AuthController::register()` suppresses exceptions without reporting; `UserController` and `SocialController` return raw exception messages; `CommentController` notifies before committing. Queue connections default to `after_commit => false`.

**Fix:** centralize safe exception rendering and reporting, dispatch externally observable effects after commit, and preserve existing response envelopes while migrating explicitly to Resources. Keep request validation failures distinct from unexpected server errors. Database-backed queues on the same transaction may already defer visibility, but that does not make other transports safe.

**Regression:** failed writes do not emit success notifications; production responses omit internal details; errors remain observable; existing clients retain their status/envelope contracts.

### D06 — P2: architecture and tests need stronger domain boundaries

`IndustryController` has 2,140 lines; `JobsFeedController` 1,581; `GroupController` 1,055; `IndustryProductController` 1,048. Line counts include comments and do not alone prove poor logic. More useful signals are mixed responsibilities, duplicate ownership/visibility rules, repeated serialization, and several authentication paths with different safety checks.

Routes are split by contributor names (`niaz.php`, `shanto.php`, `kamruzzaman.php`) instead of business areas. Large commented implementations obscure active behavior. Scheduling is split between `routes/console.php` and `routes/channels.php`. Existing tests include valuable domain coverage, but some explicitly encode unsafe contracts such as A03.

Use the architecture proposal and backlog for incremental changes. Preserve valuable existing work: database uniqueness constraints, several transactional workflows, bulk feed queries, eager loading, signature verification, typed resources, and dependency-injected services.

## Additional risks requiring targeted validation

These are follow-up investigations, not additional confirmed exploit claims:

- Social-account email linking should require provider-specific verified-email guarantees and a documented account-linking policy.
- `ProfileImageService::storeFromUrl()` follows redirects, trusts image MIME headers, and checks size after loading the body. Validate final destinations, bounded downloads, and actual decoding; current URLs generally originate from providers, so arbitrary user-controlled SSRF was not demonstrated.
- `IntegrationSetting` stores secrets as strings and caches them; masking in the admin response is not encryption at rest. Review database/cache access and secret rotation before choosing storage changes.
- Public ad tracking has debounce logic, but client assertions alone cannot prove human viewability. Review authenticated delivery tokens, rate limits, IP trust/proxy configuration, and reconciliation needs before describing metrics as fraud-free.
- Fresh migrations run through the test suite, but historical upgrades, large production tables, rollback safety, and production query plans still need a staging rehearsal.
- Inspect personal-post visibility across every comment/like/read route, not only the company-post checks. The authorization matrix in the backlog is the required completion criterion.
- `AdminAuthController` identifies the permitted administrator by a hardcoded email while protected routes use roles. Align login eligibility with the intended role/permission model and add login throttling.

## Explicitly rejected false positive

Comparing an enum-cast campaign status to its string value in Laravel collections is not automatically a defect here: the installed Laravel 13 `operatorForWhere()` normalizes both operands with `enum_value()`. Do not rewrite those comparisons as a purported bug without a failing behavior test.
