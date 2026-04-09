# Promo Codes for Early Registration Plan

Created: 2026-04-01
Author: smarcet@gmail.com
Status: PENDING
Approved: No
Iterations: 5
Worktree: No
Type: Feature

## Summary

**Goal:** Enable domain-based early registration access via two new promo code subtypes: `DomainAuthorizedSummitRegistrationDiscountCode` (with discount) and `DomainAuthorizedSummitRegistrationPromoCode` (access-only). Admins can restrict promo codes to email domains (@acme.com), TLDs (.edu, .gov), or specific email addresses. Ticket types intended for promo-code-only audiences are explicitly marked with the new `WithPromoCode` value on the existing ticket type `audience` field, making them invisible to the general public and available exclusively through any promo code (of any type) that includes them in `allowed_ticket_types` and is live during the promo code's `valid_since_date`/`valid_until_date` window. `WithPromoCode` ticket types are never available without a promo code — a "qualifying promo code" is simply any promo code that references the ticket type and is live. A new auto-discovery endpoint finds matching promo codes for the current user's email, with an `auto_apply` flag to guide frontend behavior. Additionally, existing email-linked promo code types (`MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, `SpeakerSummitRegistrationDiscountCode`) gain `auto_apply` support and are included in the auto-discovery endpoint.

**Architecture:** Two new Doctrine JOINED inheritance subtypes sharing a `DomainAuthorizedPromoCodeTrait`. New `WithPromoCode` value added to the existing `audience` ENUM on `SummitTicketType` (joins `All`, `WithInvitation`, `WithoutInvitation`). Modified `RegularPromoCodeTicketTypesStrategy` for promo-code-only audience filtering. New `GET /api/v1/summits/{summit_id}/promo-codes/all/discover` endpoint. New `AutoApplyPromoCodeTrait` providing an `auto_apply` boolean to domain-authorized types and existing email-linked types (member/speaker) via per-subtype joined tables. Any promo code type can include `WithPromoCode` ticket types in its `allowed_ticket_types` — the audience controls visibility, while the promo code type controls its own access validation independently.

**Tech Stack:** PHP 8.x, Doctrine ORM (JOINED inheritance), Laravel, MySQL (JSON columns)

**Target Repository:** `summit-api` — This SDS covers API-layer changes only. Companion SDSs are required for `summit-admin` (admin UI for managing domain-authorized promo codes, auto-apply toggles, and promo-code-only ticket type audience settings) and `summit-registration-lite` (registration frontend for auto-discovery, auto-apply UX, and promo-code-only ticket type display logic).

### Visual Context (from Proposal)

The following diagrams and mockups are from the approved proposal document and provide visual context for the feature being specified.

**User Journey — Domain-Based Registration Access Flow:**

![Domain-Based Registration Access Flow — Login through auto-discovery to checkout](assets/promo-codes-for-early-registration-access/media/image1.png)

**Admin UI — Promo Code Editor with New Fields:**

![Admin promo code editor mockup showing new fields: Allowed Email Domains, Max Per Account, Exclusive Ticket Access, Allow ticket reassignment, and Auto-apply for qualifying users](assets/promo-codes-for-early-registration-access/media/image2.png)

**Registration UI — Auto-Applied Promo Code at Checkout:**

![Registration modal mockup showing auto-applied promo code, per-account limits, and reassignment restrictions](assets/promo-codes-for-early-registration-access/media/image3.png)

**System Impact Overview:**

![Component diagram showing existing components (Registration Frontend, Promo Code API, Promo Code Table, Checkout Pipeline, Invitation System) alongside new and modified elements (New Database Columns, Identity Validation Hook, Discovery Endpoint, Frontend Auto-Discovery, Reassignment Logic)](assets/promo-codes-for-early-registration-access/media/image4.png)

## Scope

### In Scope
- New `DomainAuthorizedSummitRegistrationDiscountCode` model (extends `SummitRegistrationDiscountCode`)
- New `DomainAuthorizedSummitRegistrationPromoCode` model (extends `SummitRegistrationPromoCode`)
- Shared `DomainAuthorizedPromoCodeTrait` with common fields and logic
- `IDomainAuthorizedPromoCode` marker interface for strategy type-checking
- `AllowedEmailDomains` JSON field — supports full domains (@acme.com), TLDs (.edu, .gov), and specific emails (user@example.com)
- `QuantityPerAccount` integer field — max tickets purchasable per account with this code, enforced at BOTH discovery time and checkout time
- `remaining_quantity_per_account` calculated attribute on serializer — shows how many more tickets the current user can purchase with this code
- `AutoApply` boolean field — signals frontend whether to auto-apply at discovery time
- **New `WithPromoCode` value on the existing `audience` ENUM on `SummitTicketType`** — The ticket type `audience` field already supports `All`, `WithInvitation`, and `WithoutInvitation`. This adds `WithPromoCode` as a fourth value. Ticket types with `audience = WithPromoCode` are explicitly intended for promo-code-only distribution: they are never visible to the general public and can only be purchased through a qualifying promo code. This replaces the earlier approach of "unlocking existing ticket types" — instead, the ticket type itself declares its intended audience.
- Overridden `addTicketTypeRule()` on discount variant — only allows rules for ticket types already in `allowed_ticket_types`; does NOT write to `allowed_ticket_types` (avoids collision with parent's dual-write)
- Overridden `removeTicketTypeRuleForTicketType()` on discount variant — removes from `ticket_types_rules` only; does NOT touch `allowed_ticket_types`
- Pre-sale strategy logic: `WithPromoCode` ticket types in `allowed_ticket_types` are available during promo code's valid period. These ticket types are NEVER available through regular public sale — they require a qualifying promo code at all times.
- Auto-discovery endpoint `GET /api/v1/summits/{summit_id}/promo-codes/all/discover`
- Domain matching logic with `checkSubject` override
- CRUD support (factory, validation rules, serializer, repository) for both new domain-authorized types
- `QuantityPerAccount` checkout enforcement in `PreProcessReservationTask` (rejects orders exceeding per-account limit)
- `remaining_quantity_per_account` calculated attribute in serializers (shows remaining allowance for current user)
- **`auto_apply` support via `AutoApplyPromoCodeTrait`:** A new trait providing an `auto_apply` boolean field. Used by the new domain-authorized types (via their joined tables) and applied to existing email-linked types (`MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, `SpeakerSummitRegistrationDiscountCode`) via per-subtype `AutoApply` columns added to their existing joined tables. This is a trait — NOT a column on the base `SummitRegistrationPromoCode` table — keeping the concern scoped to only the types that participate in discovery. The discovery endpoint will match existing email-linked types by the associated member's email and return them with the `auto_apply` flag, allowing the frontend to auto-apply them just like domain-authorized codes.
- Unit tests for domain matching, strategy behavior, collision avoidance, checkout enforcement, discovery (including existing email-linked types), and audience filtering

### Out of Scope
- Frontend (Show Admin / Registration UI) changes — covered by companion SDS for `summit-admin`
- Registration frontend auto-discovery UX — covered by companion SDS for `summit-registration-lite`
- Ticket reassignment UI controls (feature 4 from proposal) — UI affair
- Email notification templates for this promo code type
- CSV import/export support for domain-authorized codes

### Companion SDSs Required
- **`summit-admin`**: Admin UI changes for managing domain-authorized promo codes (allowed email domains editor, auto-apply toggle, per-account limits), setting ticket type `audience` to `WithPromoCode`, and enabling `auto_apply` on existing member/speaker promo codes.
- **`summit-registration-lite`**: Registration frontend changes for calling the discover endpoint, auto-applying qualifying promo codes, displaying `WithPromoCode` ticket types only when unlocked by a promo code, and showing per-account limit messaging.

## Approach

**Chosen:** Two new Doctrine JOINED inheritance subtypes with a shared trait, plus a new `WithPromoCode` value on the existing `audience` ENUM on `SummitTicketType` and an `AutoApplyPromoCodeTrait` for opt-in `auto_apply` support.
**Why:** Provides both discount and access-only variants. The trait shares only the domain-specific logic (email matching, per-account limits, checkSubject) across both types without duplication — all other promo code behavior (quantity, dates, badge features, ticket type associations, checkout flow) is already provided by the existing parent classes. Follows the exact pattern established by Speaker, Member, and Sponsor subtypes (each already has discount + promo variants). The new `WithPromoCode` value on the existing ticket type `audience` ENUM makes promo-code-only intent explicit — an admin marks a ticket type as `WithPromoCode` the same way they'd mark one `WithInvitation`, making the intent clear and the filtering logic consistent with existing audience handling. Using a dedicated `AutoApplyPromoCodeTrait` keeps the `auto_apply` concern scoped to only the types that need it — no base class pollution. Existing email-linked types (member, speaker) use the trait via per-subtype `AutoApply` columns on their existing joined tables.
**Alternatives considered:** (1) Single subtype only (discount) — rejected by stakeholder; access-only variant is needed. (2) Adding domain fields to base class — rejected; pollutes all promo code types. (3) Pre-sale date-window approach (promo code valid period unlocks existing ticket types before their sale period) — rejected by stakeholder in favor of explicit `audience` field; date-window approach was fragile and confusing for admins. (4) Separate `exclusive_ticket_types` M2M — rejected; reusing inherited `allowed_ticket_types` with audience filtering is cleaner.

## Context for Implementer

> Write for an implementer who has never seen the codebase.

- **What's inherited (already exists) vs. what's new:**
  The promo code system already has a well-established subtype pattern (Speaker, Member, Sponsor each have discount + promo variants). The new domain-authorized types follow the same pattern and **inherit the majority of their behavior from existing parent classes.** Here is what already exists and does NOT need to be built:
  - `code`, `description`, `quantity_available`, `quantity_used`, `valid_since_date`, `valid_until_date`, `tags` — all inherited from `SummitRegistrationPromoCode` base class
  - `allowed_ticket_types` M2M (which ticket types the code applies to) — inherited from base class
  - `canBeAppliedTo()`, `isLive()`, `canSell()` — inherited validation logic (`canBeAppliedTo()` is overridden on discount variant only — see Task 3 / Truth #15)
  - `amount`, `rate`, `ticket_types_rules` (per-type discount amounts) — inherited from `SummitRegistrationDiscountCode` parent (discount variant only)
  - Badge features, notes, allows to delegate, allow to reassign — all inherited from base class
  - The entire checkout pipeline, order flow, and payment processing — completely untouched
  - The serializer base classes, CRUD controller, service layer patterns — all existing; new types plug in

  **What IS new (only these parts need to be built):**
  - `DomainAuthorizedPromoCodeTrait` — the email domain matching logic (`allowed_email_domains` JSON field, `quantity_per_account` field, `checkSubject`/`matchesEmailDomain` methods)
  - Two thin model subclasses that extend existing parents and use the trait — they are mostly boilerplate (joined table, discriminator entry, `getClassName()`)
  - Collision avoidance overrides on the discount variant (`addTicketTypeRule`, `removeTicketTypeRuleForTicketType`) — these are overrides, not new methods
  - The discovery endpoint (`GET .../discover`) — this is genuinely new behavior
  - `WithPromoCode` value on the existing `audience` ENUM — a new value, not a new field
  - `AutoApplyPromoCodeTrait` — a new trait with `auto_apply` boolean, used by domain-authorized types and applied to existing email-linked types via per-subtype joined table columns
  - Wiring: factory cases, validation rule cases, serializer registrations, repository SQL joins — following the exact same patterns already established for Speaker/Member/Sponsor types

- **Patterns to follow:**
  - Existing discount code subtypes: `SponsorSummitRegistrationDiscountCode` (app/Models/Foundation/Summit/Registration/PromoCodes/SponsorSummitRegistrationDiscountCode.php) is the closest pattern — extends `SummitRegistrationDiscountCode`, has its own joined table, overrides `checkSubject` via trait
  - Existing promo code subtypes: `SpeakerSummitRegistrationPromoCode` (app/Models/Foundation/Summit/Registration/PromoCodes/SpeakerSummitRegistrationPromoCode.php) — extends base `SummitRegistrationPromoCode` directly
  - Factory pattern: `SummitPromoCodeFactory::build()` (app/Models/Foundation/Summit/Factories/SummitPromoCodeFactory.php:41) creates by `class_name`, `::populate()` sets fields per type
  - Validation rules: `PromoCodesValidationRulesFactory` (app/Http/Controllers/Apis/Protected/Summit/Factories/Registration/PromoCodesValidationRulesFactory.php) — `buildForAdd` and `buildForUpdate` methods with per-type switch cases
  - Serializer registration: `SerializerRegistry.php:442-506` — each type gets Public + CSV + PreValidation entries
  - Discriminator map: `SummitRegistrationPromoCode.php:31` — must add TWO new entries
  - Repository: `DoctrineSummitRegistrationPromoCodeRepository.php` — uses raw SQL with LEFT JOINs for all subtypes

- **Conventions:**
  - Model class names match DB table names (e.g., class `SponsorSummitRegistrationDiscountCode` → table `SponsorSummitRegistrationDiscountCode`)
  - ClassName constants are UPPER_SNAKE_CASE (e.g., `SPONSOR_DISCOUNT_CODE`)
  - `checkSubject(string $email, ?string $company): bool` — throws `ValidationException` on failure
  - Promo codes always stored uppercase via `setCode()`

- **Key files:**
  - `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php` — base class, discriminator map, `allowed_ticket_types` M2M, `canBeAppliedTo()`
  - `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationDiscountCode.php` — discount code parent with amount/rate, `addTicketTypeRule()` (dual-write collision source), `removeTicketTypeRuleForTicketType()`
  - `app/Models/Foundation/Summit/Registration/SummitTicketType.php` — `canSell()`, `sales_start_date`/`sales_end_date`, existing `audience` ENUM (adding `WithPromoCode` to `All`/`WithInvitation`/`WithoutInvitation`)
  - `app/Models/Foundation/Summit/Registration/PromoCodes/Strategies/RegularPromoCodeTicketTypesStrategy.php` — ticket type filtering logic, `getTicketTypes()`, `applyPromo2TicketType()`
  - `app/Models/Foundation/Summit/Registration/PromoCodes/PromoCodesConstants.php` — valid class names list
  - `app/Models/Foundation/Summit/Factories/SummitPromoCodeFactory.php` — create/populate
  - `app/Http/Controllers/Apis/Protected/Summit/Factories/Registration/PromoCodesValidationRulesFactory.php` — validation
  - `app/ModelSerializers/SerializerRegistry.php:434-506` — serializer mapping
  - `app/ModelSerializers/Summit/Registration/PromoCodes/SummitRegistrationDiscountCodeSerializer.php` — unsets `allowed_ticket_types` in output (new discount serializer must re-add it)
  - `app/Repositories/Summit/DoctrineSummitRegistrationPromoCodeRepository.php` — queries with raw SQL joins
  - `routes/api_v1.php` — route definitions
  - `app/Http/Controllers/Apis/Protected/Summit/OAuth2SummitPromoCodesApiController.php` — controller
  - `app/Services/Model/Imp/SummitPromoCodeService.php` — service layer
  - `app/Services/Model/Imp/SummitOrderService.php` — order checkout flow, `PreProcessReservationTask` validates promo codes during order creation (line ~995)

- **Gotchas:**
  - The raw SQL in `DoctrineSummitRegistrationPromoCodeRepository::getIdsBySummit()` has LEFT JOINs for EVERY subtype table. Must add TWO new table joins there (one per new type).
  - `SummitRegistrationDiscountCode::getMetadata()` calls `unset($parent_metadata['allowed_ticket_types'])` — the new discount subtype's serializer must RE-ADD `allowed_ticket_types` to output since it's the primary collection.
  - `SummitRegistrationDiscountCode::addTicketTypeRule()` writes to BOTH `ticket_types_rules` AND `allowed_ticket_types`. `removeTicketTypeRuleForTicketType()` removes from both. The discount subtype MUST override both to avoid corrupting the `allowed_ticket_types` collection. The promo code variant does NOT have this issue (no `ticket_types_rules` on base class).
  - The `SummitTicketTypeWithPromo` wrapper proxies all methods — no changes needed there since it already handles discount codes.

- **Domain context:**
  - "Promo code" = either a flat access code (no discount) or a discount code (with amount/rate). This feature adds both variants.
  - `allowed_ticket_types` M2M on promo code means "this code can be applied to these ticket types" (restriction). For discount codes, `ticket_types_rules` provides per-type discount amounts.
  - **Ticket type audience model:** Ticket types already have an `audience` ENUM field with values `All` (default — visible to everyone), `WithInvitation` (requires invitation), and `WithoutInvitation` (only for non-invited users). This feature adds `WithPromoCode` (visible only to users with a qualifying promo code). When an admin creates a ticket type intended for a specific group (e.g., "Partner Pass," "Student Rate"), they set `audience = WithPromoCode`. This ticket type is then completely hidden from public registration and only appears when any promo code (of any type — domain-authorized, email-linked, or plain generic) includes it in `allowed_ticket_types` and is live. The promo code's `valid_since_date`/`valid_until_date` defines when these ticket types are available to qualifying users. Ticket types with other audience values (`All`, `WithInvitation`, `WithoutInvitation`) continue to work exactly as they do today.
  - **Audience vs. `allowed_ticket_types` — two separate concerns:** The `audience` field on a ticket type controls **visibility** (who can see it). The `allowed_ticket_types` on a promo code controls **applicability** (which ticket types the code applies to). These are independent. A promo code can reference ticket types of ANY audience value: a domain-authorized code might give a .edu discount on a General Admission ticket (`audience = All`, publicly visible) *and* unlock a hidden Student Rate ticket (`audience = WithPromoCode`). Setting `audience = WithPromoCode` simply hides the ticket type from anyone who doesn't have a qualifying promo code — it does NOT restrict which promo codes can reference it. Conversely, a promo code is not limited to only `WithPromoCode` ticket types. **Definition of "qualifying promo code":** Any promo code of any type (domain-authorized, email-linked, or plain generic) that includes the `WithPromoCode` ticket type in its `allowed_ticket_types` and is live. There is no type restriction — the promo code's own validation logic (e.g., `checkSubject` for domain-authorized codes) handles access control independently of the audience check.
  - **Collision avoidance (discount variant only):** The parent `SummitRegistrationDiscountCode::addTicketTypeRule()` writes to BOTH `ticket_types_rules` AND `allowed_ticket_types`. On the new discount subtype, both `addTicketTypeRule()` and `removeTicketTypeRuleForTicketType()` are overridden: `addTicketTypeRule()` only writes to `ticket_types_rules` (requires type already in `allowed_ticket_types`), `removeTicketTypeRuleForTicketType()` only removes from `ticket_types_rules`. This makes `allowed_ticket_types` the master list, with `ticket_types_rules` as an optional per-type discount configuration subset.
  - **Existing email-linked promo codes:** The existing types `MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, and `SpeakerSummitRegistrationDiscountCode` are already linked to specific email addresses/logins via their associated member/speaker. These types gain an `auto_apply` checkbox via the `AutoApplyPromoCodeTrait` (with an `AutoApply` column added to each subtype's existing joined table) and are included in the auto-discovery endpoint. The discovery endpoint matches them by the associated member's email address. This means speakers and members no longer need to remember or type their promo codes — they are auto-discovered and optionally auto-applied at login.
  - `canSell()` checks quantity + date window. `isLive()` checks promo code date window only.

## Assumptions

- MySQL version supports JSON columns and JSON_CONTAINS (MySQL 5.7+) — supported by existing JSON column usage in the codebase — All tasks depend on this
- `QuantityPerAccount` is enforced at BOTH discovery time (exclude exhausted codes, expose `remaining_quantity_per_account` calculated field) and checkout time (reject orders exceeding limit) — Tasks 5, 8, 9, 10 depend on this
- Frontend will call the new discover endpoint and use `auto_apply` to determine behavior — Tasks 8, 9 depend on this
- Domain patterns are case-insensitive (e.g., @Acme.com matches user@acme.com) — Task 2 depends on this
- Ticket types with `audience = WithPromoCode` are never visible in public registration — they require a qualifying promo code — Tasks 3, 4, 6 depend on this
- Both discount and promo code variants share the same domain-authorization behavior — Task 2 (trait) depends on this
- Existing email-linked promo codes (member/speaker) already have an associated member with an email — discovery matches on that email — Task 11 depends on this
- The `auto_apply` field is provided via `AutoApplyPromoCodeTrait` with per-subtype `AutoApply` columns on joined tables — NOT on the base class — Tasks 1, 2, 11 depend on this

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Raw SQL joins in repository become too complex with TWO new tables | Medium | Medium | Follow exact pattern of existing LEFT JOINs; both tables have identical structure |
| JSON domain matching in MySQL is slow for high-volume summits | Low | Medium | Domains are matched at application level during discovery (not in SQL); result set is small |
| Existing `canBeAppliedTo` rejects free ticket types for discount codes — domain-authorized discount codes need free `WithPromoCode` types for comp/speaker passes | Medium | High | Override `canBeAppliedTo` on the discount variant per Truth #15 to skip the free-ticket guard; covered by integration test in Task 12 |
| `WithPromoCode` ticket types accidentally visible if strategy filtering has a bug | Low | High | Strategy must check `audience` field first; unit tests cover this explicitly |
| Adding `AutoApply` columns to four existing joined tables (member/speaker types) requires migration coordination | Medium | Low | Follow exact pattern of existing column additions to joined tables; column defaults to `false` so no behavioral change for existing records |
| Existing member/speaker promo codes have different association patterns than domain-authorized codes | Medium | Medium | Discovery endpoint handles both patterns: domain matching for new types, member email matching for existing types |

## Goal Verification

### Truths
1. Admin can create both `DomainAuthorizedSummitRegistrationDiscountCode` (class_name=`DOMAIN_AUTHORIZED_DISCOUNT_CODE`) and `DomainAuthorizedSummitRegistrationPromoCode` (class_name=`DOMAIN_AUTHORIZED_PROMO_CODE`) via the existing promo codes API
2. Both types store `allowed_email_domains` (JSON) and `quantity_per_account` (integer) via `DomainAuthorizedPromoCodeTrait`; `auto_apply` (boolean) via `AutoApplyPromoCodeTrait` — both stored on per-subtype joined tables, NOT on the base class
3. Both types use inherited `allowed_ticket_types` — any ticket type can be added regardless of its `audience` value
4. Adding a `ticket_types_rule` on the discount variant fails if the ticket type is not already in `allowed_ticket_types`
5. Ticket types with `audience = WithPromoCode` are NEVER returned by public ticket type queries — they only appear when a qualifying promo code includes them in `allowed_ticket_types` and the promo code is live
6. Ticket types with `audience = All` continue to behave exactly as they do today (visible during their sale window, with or without a promo code)
7. `WithPromoCode` ticket types in `allowed_ticket_types` are available during the promo code's `valid_since_date`/`valid_until_date` window — they are never available outside of a qualifying promo code
8. `GET /api/v1/summits/{summit_id}/promo-codes/all/discover` returns qualifying promo codes for the current user: domain-authorized types matched by email domain, plus existing email-linked types (member/speaker promo & discount codes) matched by associated member email — all including the `auto_apply` flag
9. Discovery endpoint excludes codes where the user has already purchased `quantity_per_account` or more tickets (i.e., count equals the limit — no remaining allowance) and exposes `remaining_quantity_per_account` as a calculated attribute
10. Checkout rejects orders that would exceed `quantity_per_account` for a domain-authorized promo code
11. `checkSubject` validation rejects users whose email doesn't match any pattern in `allowed_email_domains`
12. Existing email-linked promo codes (`MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, `SpeakerSummitRegistrationDiscountCode`) are returned by the discovery endpoint when the current user's email matches the associated member/speaker email — regardless of `auto_apply` value. The `auto_apply` flag is included in the response as a frontend hint (true → apply silently, false → suggest to user) but does NOT filter results server-side
13. All existing promo code types and endpoints continue working unchanged (new `auto_apply` column defaults to `false`)
14. The discovery endpoint's email matching is always derived from the authenticated principal via `resource_server_context` — the endpoint accepts no email-related query parameter and ignores any that are sent, preventing enumeration of other users' qualifying codes
15. Domain-authorized discount codes can be applied to ticket types in their `allowed_ticket_types` regardless of ticket price — the access decision is governed by `allowed_email_domains` and `quantity_per_account`, not by ticket cost. `canBeAppliedTo()` is overridden on the discount variant to skip the free-ticket guard while preserving all other checks (date window, quantity, etc.). This preserves the symmetry from Resolved Decision #8 (audience controls visibility, type controls access) at apply-time as well as discovery-time

### Artifacts
- `database/migrations/model/Version20260401XXXXXX.php` — migration (two new joined tables + `WithPromoCode` added to existing `audience` ENUM on `SummitTicketType` + `AutoApply` columns on four existing email-linked subtype joined tables)
- `app/Models/Foundation/Summit/Registration/PromoCodes/DomainAuthorizedPromoCodeTrait.php` — shared trait
- `app/Models/Foundation/Summit/Registration/PromoCodes/IDomainAuthorizedPromoCode.php` — marker interface
- `app/Models/Foundation/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationDiscountCode.php` — discount model
- `app/Models/Foundation/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationPromoCode.php` — promo code model
- `app/Models/Foundation/Summit/Registration/SummitTicketType.php` — modified (new `WithPromoCode` audience value + `isPromoCodeOnly()` helper)
- `app/Models/Foundation/Summit/Registration/PromoCodes/AutoApplyPromoCodeTrait.php` — new trait providing `auto_apply` boolean
- `app/ModelSerializers/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationDiscountCodeSerializer.php` — discount serializer
- `app/ModelSerializers/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationPromoCodeSerializer.php` — promo code serializer
- `tests/Unit/Services/DomainAuthorizedPromoCodeTest.php` — unit tests

## Progress Tracking

- [x] Task 1: Database migration (two new joined tables + `WithPromoCode` audience value + `AutoApply` on four existing email-linked subtype tables)
- [x] Task 2: Traits and interfaces (DomainAuthorizedPromoCodeTrait, AutoApplyPromoCodeTrait, IDomainAuthorizedPromoCode)
- [x] Task 3: DomainAuthorizedSummitRegistrationDiscountCode model
- [x] Task 4: DomainAuthorizedSummitRegistrationPromoCode model
- [x] Task 5: SummitTicketType — add `WithPromoCode` audience value and filtering logic
- [x] Task 6: Factory, validation rules, and serializers (both new types + ticket type audience) — see D3
- [x] Task 7: Modify RegularPromoCodeTicketTypesStrategy for audience-based filtering
- [x] Task 8: Repository — discovery query and raw SQL joins (both tables)
- [x] Task 9: Auto-discovery endpoint (route, controller, service) — including existing email-linked types
- [x] Task 10: QuantityPerAccount checkout enforcement — see D4
- [x] Task 11: Auto-apply support for existing email-linked promo codes (member/speaker)
- [x] Task 12: Unit tests

**Total Tasks:** 12 | **Completed:** 12 | **Remaining:** 0

## Implementation Deviations Log

Deviations from the SDS captured during implementation. Each entry is either **OPEN** (needs fix), **ACCEPTED** (intentional, no fix needed), or **RESOLVED** (fixed post-implementation).

| # | Deviation | Severity | Status | Tasks | Detail |
|---|-----------|----------|--------|-------|--------|
| D1 | Trait file locations | NIT | ACCEPTED | 2 | SDS specifies traits in `PromoCodes/` directly. Existing codebase convention puts traits in `PromoCodes/Traits/`. Implementation followed SDS paths. Acceptable — no functional impact, but future cleanup may move them to `Traits/` for consistency. |
| D2 | `addTicketTypeRule` accesses private parent field via getter | NIT | ACCEPTED | 3 | SDS implies direct `$this->ticket_types_rules->add()` but parent declares `$ticket_types_rules` as `private`. Implementation uses `$this->getTicketTypesRules()->add()` and `canBeAppliedTo()` for the allowed_ticket_types membership check. Functionally equivalent. |
| D3 | `allowed_email_domains` validation uses `sometimes|json` instead of custom rule | SHOULD-FIX | OPEN | 6 | SDS explicitly states generic `'sometimes|json'` is insufficient — would accept `[123, null, ""]` which silently never matches. Needs a custom validation rule enforcing each entry matches `@domain`, `.tld`, or `user@email` format. |
| D4 | `quantity_per_account` check lacks pessimistic lock | MUST-FIX | OPEN | 10 | SDS specifies `SELECT ... FOR UPDATE` on the promo code row within the quantity check. Implementation adds the check in `PreProcessReservationTask` which runs before `ApplyPromoCodeTask` (which holds the lock). This creates a TOCTOU window — two concurrent requests could both pass the pre-check. The quantity check needs to move inside the locked transaction boundary, or `PreProcessReservationTask` needs its own pessimistic lock. |
| D5 | Discovery response uses manual array instead of `PagingResponse` object | NIT | ACCEPTED | 9 | SDS says "uses the standard `PagingResponse` envelope." Implementation constructs an identical JSON shape manually. Acceptable — output is identical, and the endpoint doesn't actually paginate. |
| D6 | Task 8 implemented before Task 11 (dependency violation) | NIT | ACCEPTED | 8, 11 | SDS declares Task 8 depends on Task 11. Implementation order was reversed. No functional issue — the repository query fetches member/speaker entities by type regardless of whether `AutoApplyPromoCodeTrait` is applied yet. |
| D7 | `addAllowedTicketType` overrides are no-ops | NIT | ACCEPTED | 3, 4 | SDS specifies overriding `addAllowedTicketType()` on both types. The override just calls `parent::addAllowedTicketType()` which already accepts any ticket type. Present for documentation intent per SDS, but functionally dead code. |

### Resolution Plan

- **D3 (OPEN):** Create a custom Laravel validation rule class (e.g., `AllowedEmailDomainsRule`) that decodes the JSON and validates each entry matches `^@[\w.-]+$`, `^\.\w+$`, or `^[^@]+@[\w.-]+$`. Apply in both `buildForAdd` and `buildForUpdate` for domain-authorized types.
- **D4 (OPEN):** Move the `quantity_per_account` check into `ApplyPromoCodeTask` (which already holds a pessimistic lock via `getByValueExclusiveLock`), or add a `SELECT ... FOR UPDATE` on the promo code row in `PreProcessReservationTask` by passing the transaction service. The former is cleaner since the lock already exists.

## Implementation Tasks

### Task 1: Database Migration

**Objective:** Create migration for both new joined tables, the new `WithPromoCode` value on the existing `audience` ENUM on `SummitTicketType`, and `AutoApply` columns on the four existing email-linked subtype joined tables.
**Dependencies:** None
**Mapped Scenarios:** None

**Files:**
- Create: `database/migrations/model/Version20260401150000.php`

**Key Decisions / Notes:**
- Follow pattern of existing joined tables (e.g., `SponsorSummitRegistrationDiscountCode`)
- Table 1: `DomainAuthorizedSummitRegistrationDiscountCode` with columns: `ID` (FK to SummitRegistrationPromoCode.ID), `AllowedEmailDomains` (JSON), `QuantityPerAccount` (INT DEFAULT 0, where 0 = unlimited)
- Table 2: `DomainAuthorizedSummitRegistrationPromoCode` with columns: `ID` (FK to SummitRegistrationPromoCode.ID), `AllowedEmailDomains` (JSON), `QuantityPerAccount` (INT DEFAULT 0)
- ALTER `SummitTicketType`: modify existing `Audience` ENUM to add `WithPromoCode` value — `ALTER TABLE SummitTicketType MODIFY Audience ENUM('All', 'WithInvitation', 'WithoutInvitation', 'WithPromoCode') NOT NULL DEFAULT 'All'`
- ALTER four existing email-linked subtype joined tables to add `AutoApply` column — `TINYINT(1) NOT NULL DEFAULT 0`:
  - `ALTER TABLE MemberSummitRegistrationPromoCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0`
  - `ALTER TABLE MemberSummitRegistrationDiscountCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0`
  - `ALTER TABLE SpeakerSummitRegistrationPromoCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0`
  - `ALTER TABLE SpeakerSummitRegistrationDiscountCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0`
- NOTE: `AutoApply` is NOT added to the base `SummitRegistrationPromoCode` table — it is a per-subtype concern managed via `AutoApplyPromoCodeTrait`, keeping the base class clean
- NO new M2M join tables — both types reuse the existing `SummitRegistrationPromoCode_AllowedTicketTypes` M2M from the base class

**Definition of Done:**
- [ ] Migration runs without errors (`up` and `down`)
- [ ] Both new tables exist with correct schema
- [ ] `SummitTicketType.Audience` ENUM now includes `WithPromoCode` alongside existing values (`All`, `WithInvitation`, `WithoutInvitation`)
- [ ] `AutoApply` column exists on all four existing email-linked subtype tables with default `0`
- [ ] All existing data is unchanged (defaults applied)
- [ ] No diagnostics errors

**Verify:**
- `php artisan doctrine:migrations:migrate --no-interaction`

**Review Follow-ups:**
- [x] **Missing `ClassName` discriminator ENUM widening (MUST-FIX):** The migration created both new joined tables but never widened the `ClassName` ENUM column on `SummitRegistrationPromoCode` — the Doctrine discriminator column used for JOINED inheritance. Every insert into either new type would have failed or silently corrupted. Fixed by adding `ALTER TABLE SummitRegistrationPromoCode MODIFY ClassName ENUM(...)` in `up()` (appending `DomainAuthorizedSummitRegistrationDiscountCode` and `DomainAuthorizedSummitRegistrationPromoCode` after the existing 12 values) and a corresponding revert in `down()` placed after the joined tables are dropped so no rows reference the removed values.
- [x] **`down()` narrows `Audience` ENUM without a data guard (SHOULD-FIX):** If any `SummitTicketType` rows carried `Audience = 'WithPromoCode'` at rollback time, MySQL would hard-error in strict mode or silently coerce to an empty string in non-strict mode. Fixed by adding `UPDATE SummitTicketType SET Audience = 'All' WHERE Audience = 'WithPromoCode'` immediately before the `MODIFY Audience` statement in `down()`.

---

### Task 2: Traits and Interfaces (DomainAuthorizedPromoCodeTrait, AutoApplyPromoCodeTrait, IDomainAuthorizedPromoCode)

**Objective:** Create the shared domain-authorization trait with email matching fields and logic, a separate auto-apply trait for the `auto_apply` boolean, and a marker interface for strategy type-checking.
**Dependencies:** None
**Mapped Scenarios:** None

**Files:**
- Create: `app/Models/Foundation/Summit/Registration/PromoCodes/DomainAuthorizedPromoCodeTrait.php`
- Create: `app/Models/Foundation/Summit/Registration/PromoCodes/AutoApplyPromoCodeTrait.php`
- Create: `app/Models/Foundation/Summit/Registration/PromoCodes/IDomainAuthorizedPromoCode.php`

**Key Decisions / Notes:**
- **Trait properties** (with ORM column attributes):
  - `$allowed_email_domains` — `#[ORM\Column(name: 'AllowedEmailDomains', type: 'json', nullable: true)]`, default `[]`
  - `$quantity_per_account` — `#[ORM\Column(name: 'QuantityPerAccount', type: 'integer')]`, default `0`
- **Note:** `auto_apply` is provided by a SEPARATE `AutoApplyPromoCodeTrait` (see below) — NOT on this trait and NOT on the base class. The domain-authorized types use BOTH traits.
- **Trait methods:**
  - Getters/setters for `allowed_email_domains` and `quantity_per_account`
  - `checkSubject(string $email, ?string $company): bool` — validates email against `allowed_email_domains`, throws `ValidationException` if no match
  - `matchesEmailDomain(string $email): bool` — returns bool (for discovery use, no exception)
  - Domain matching logic (case-insensitive):
    - Pattern starts with `@` (e.g., `@acme.com`) → match email domain exactly
    - Pattern starts with `.` (e.g., `.edu`) → match email suffix (TLD/subdomain)
    - Pattern contains `@` but no leading `@` (e.g., `user@example.com`) → exact email match
  - If `allowed_email_domains` is empty → pass (no restriction)
- **Interface** `IDomainAuthorizedPromoCode`:
  - `getAllowedEmailDomains(): array`
  - `getQuantityPerAccount(): int`
  - `matchesEmailDomain(string $email): bool`
- **`AutoApplyPromoCodeTrait`** — a separate, lightweight trait providing:
  - `$auto_apply` — `#[ORM\Column(name: 'AutoApply', type: 'boolean')]`, default `false`
  - Getter/setter: `getAutoApply(): bool`, `setAutoApply(bool $auto_apply): void`
  - This trait is used by: (1) the new domain-authorized types (both discount and promo variants), and (2) the four existing email-linked types (`MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, `SpeakerSummitRegistrationDiscountCode`). Each type that uses this trait stores `AutoApply` on its own joined table — NOT on the base `SummitRegistrationPromoCode` table.
  - Keeping this as a separate trait (rather than bundling it into `DomainAuthorizedPromoCodeTrait`) allows existing email-linked types to opt in to auto-apply without also pulling in domain-matching logic they don't need.

**Definition of Done:**
- [ ] `DomainAuthorizedPromoCodeTrait` compiles without errors
- [ ] `AutoApplyPromoCodeTrait` compiles without errors
- [ ] Interface defines required method signatures
- [ ] Domain matching handles all pattern types: `@domain`, `.tld`, `exact@email`
- [ ] Matching is case-insensitive
- [ ] `matchesEmailDomain` returns bool, `checkSubject` throws on failure
- [ ] No diagnostics errors

**Verify:**
- Unit test for matching logic

**Review Follow-ups:**
- [x] **`matchesEmailDomain()` false positive on no-`@` input (SHOULD-FIX):** If called with a string containing no `@` (e.g. `"alice.edu"`), `strpos` returns `false`, `substr` coerces the offset to `0`, and the full string is used as `$emailDomain`. This causes `str_ends_with('alice.edu', '.edu')` to return `true` — a false positive. Fix: add `if (strpos($email, '@') === false) return false;` immediately after the `if (empty($email)) return false;` guard in `matchesEmailDomain()` (`DomainAuthorizedPromoCodeTrait.php`).

---

### Task 3: DomainAuthorizedSummitRegistrationDiscountCode Model

**Objective:** Create the discount variant entity class with collision avoidance overrides and register in the discriminator map.
**Dependencies:** Task 1, Task 2
**Mapped Scenarios:** None

**Files:**
- Create: `app/Models/Foundation/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationDiscountCode.php`
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php` (discriminator map)
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/PromoCodesConstants.php` (valid class names)

**Key Decisions / Notes:**
- Extends `SummitRegistrationDiscountCode`, uses `DomainAuthorizedPromoCodeTrait`, implements `IDomainAuthorizedPromoCode`
- `ClassName = 'DOMAIN_AUTHORIZED_DISCOUNT_CODE'`
- ORM: `#[ORM\Table(name: 'DomainAuthorizedSummitRegistrationDiscountCode')]`, `#[ORM\Entity]`
- No new M2M — uses inherited `$allowed_ticket_types` from `SummitRegistrationPromoCode`
- Add `getClassName()`, `$metadata` static array
- **Override `addAllowedTicketType(SummitTicketType $type)`** — call parent to add. Any ticket type can be added regardless of `audience` value (both `All` and `WithPromoCode` are valid).
- **Override `addTicketTypeRule(SummitRegistrationDiscountCodeTicketTypeRule $rule)`** — check that `$rule->getTicketType()` already exists in `$this->allowed_ticket_types` (throw ValidationException if not). Add rule to `$this->ticket_types_rules` only — do NOT call parent (which writes to `allowed_ticket_types`). Set bidirectional `$rule->setDiscountCode($this)`. Check for duplicate via `isOnRules()`.
- **Override `removeTicketTypeRuleForTicketType(SummitTicketType $type)`** — remove from `$this->ticket_types_rules` only — do NOT touch `$this->allowed_ticket_types`.
- **Override `canBeAppliedTo(SummitTicketType $ticketType): bool`** — the parent `SummitRegistrationDiscountCode::canBeAppliedTo()` rejects free ticket types (cost = 0) because applying a discount to a free ticket doesn't make sense for regular discount codes. However, domain-authorized discount codes serve a dual purpose: they can discount regular ticket types AND grant access to free `WithPromoCode` ticket types (e.g., comp passes, speaker passes). Override to skip the free-ticket guard while preserving all other validation checks (date window, sale window, quantity, `allowed_ticket_types` membership, etc.). See Truth #15.
- Add to discriminator map on `SummitRegistrationPromoCode.php:31`
- Add `DOMAIN_AUTHORIZED_DISCOUNT_CODE` to `PromoCodesConstants::$valid_class_names`

**Definition of Done:**
- [ ] Model class compiles without errors
- [ ] Discriminator map includes `DomainAuthorizedSummitRegistrationDiscountCode`
- [ ] `PromoCodesConstants::$valid_class_names` includes the new ClassName
- [ ] `addTicketTypeRule()` rejects rules for types not in `allowed_ticket_types`
- [ ] `addTicketTypeRule()` does NOT write to `allowed_ticket_types`
- [ ] `removeTicketTypeRuleForTicketType()` does NOT touch `allowed_ticket_types`
- [ ] `canBeAppliedTo()` allows free ticket types in `allowed_ticket_types` (does not reject on cost = 0)
- [ ] Domain-authorized discount codes interact correctly with `WithPromoCode` ticket types at every layer: admin create → discovery → auto-apply → apply-time validation → checkout
- [ ] No diagnostics errors

**Verify:**
- `php artisan clear-compiled && php artisan cache:clear`

**Review Follow-ups:**
- [x] **`addTicketTypeRule()` guard allows rules on empty `allowed_ticket_types` (MUST-FIX):** The guard `if (!$this->canBeAppliedTo($ticketType))` passes when `allowed_ticket_types` is empty because `SummitRegistrationPromoCode::canBeAppliedTo()` returns `true` in that case. Violates Truth #4. Fix: replace with a direct membership check — `if (!$this->allowed_ticket_types->contains($ticketType))` — in `DomainAuthorizedSummitRegistrationDiscountCode::addTicketTypeRule()`.
- [x] **Inherited `removeTicketTypeRule()` mutates `allowed_ticket_types` (SHOULD-FIX):** `SummitRegistrationDiscountCode::removeTicketTypeRule(SummitRegistrationDiscountCodeTicketTypeRule $rule)` (line 172) calls `$this->allowed_ticket_types->add($rule->getTicketType())`, re-adding the ticket type to the master list. No current call sites, but the method is public. Override it in `DomainAuthorizedSummitRegistrationDiscountCode` to remove from `ticket_types_rules` only (same pattern as `removeTicketTypeRuleForTicketType`).

---

### Task 4: DomainAuthorizedSummitRegistrationPromoCode Model

**Objective:** Create the access-only (non-discount) variant entity class and register in the discriminator map.
**Dependencies:** Task 1, Task 2
**Mapped Scenarios:** None

**Files:**
- Create: `app/Models/Foundation/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationPromoCode.php`
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php` (discriminator map — add second entry)
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/PromoCodesConstants.php` (valid class names — add second entry)

**Key Decisions / Notes:**
- Extends `SummitRegistrationPromoCode` (base class, NOT the discount variant), uses `DomainAuthorizedPromoCodeTrait`, implements `IDomainAuthorizedPromoCode`
- `ClassName = 'DOMAIN_AUTHORIZED_PROMO_CODE'`
- ORM: `#[ORM\Table(name: 'DomainAuthorizedSummitRegistrationPromoCode')]`, `#[ORM\Entity]`
- No collision issue — the base class has no `addTicketTypeRule()` or `removeTicketTypeRuleForTicketType()` methods
- **Override `addAllowedTicketType(SummitTicketType $type)`** — call parent to add. Any ticket type can be added regardless of `audience` value.
- Add `getClassName()`, `$metadata` static array
- Add to discriminator map on `SummitRegistrationPromoCode.php:31`
- Add `DOMAIN_AUTHORIZED_PROMO_CODE` to `PromoCodesConstants::$valid_class_names`

**Definition of Done:**
- [ ] Model class compiles without errors
- [ ] Discriminator map includes `DomainAuthorizedSummitRegistrationPromoCode`
- [ ] `PromoCodesConstants::$valid_class_names` includes the new ClassName
- [ ] No diagnostics errors

**Verify:**
- `php artisan clear-compiled && php artisan cache:clear`

**Review Follow-ups:**
- [x] **Misleading comment on no-op `addAllowedTicketType` override (NIT):** The override at `DomainAuthorizedSummitRegistrationPromoCode.php:55` only calls `parent::addAllowedTicketType()` and does not change behavior — the base implementation does not enforce any audience gate. The "regardless of audience value" comment implies special logic that isn't there. Confirmed no-op and no correctness risk. Accepted per D7; comment is documentation-intent only.

---

### Task 5: SummitTicketType — Add `WithPromoCode` Audience Value and Filtering Logic

**Objective:** Add the `WithPromoCode` value to the existing `audience` ENUM on `SummitTicketType` so ticket types can be explicitly marked for promo-code-only distribution.
**Dependencies:** Task 1
**Mapped Scenarios:** None

**Files:**
- Modify: `app/Models/Foundation/Summit/Registration/SummitTicketType.php`
- Modify: ticket type factory — add `WithPromoCode` to valid audience values
- Modify: ticket type validation rules — update audience validation to include `WithPromoCode` (`'sometimes|string|in:All,WithInvitation,WithoutInvitation,WithPromoCode'`)

**Key Decisions / Notes:**
- The `audience` field, getter/setter, and serializer already exist on `SummitTicketType`. The current valid values are `All`, `WithInvitation`, `WithoutInvitation`.
- Add new constant: `AUDIENCE_WITH_PROMO_CODE = 'WithPromoCode'`
- Add helper: `isPromoCodeOnly(): bool` — returns `$this->audience === self::AUDIENCE_WITH_PROMO_CODE`
- Update the ENUM column definition to include `WithPromoCode` (via migration in Task 1)
- Update anywhere that validates the `audience` value (factory, validation rules) to accept `WithPromoCode`
- **Filtering:** The strategy (Task 7) will use `isPromoCodeOnly()` to exclude `WithPromoCode` ticket types from public queries. This means `WithPromoCode` ticket types are invisible in the standard ticket type listing unless a qualifying promo code is in play.
- **Interaction with existing audience values:** `WithPromoCode` is independent of `WithInvitation`/`WithoutInvitation`. A ticket type has exactly one audience value. If a ticket type is `WithPromoCode`, it is not affected by invitation logic — it is only accessible via promo code.
- **No restriction on which promo codes can reference which audience:** Any promo code of any type (domain-authorized, email-linked, or plain generic) can have `WithPromoCode` ticket types in its `allowed_ticket_types`. The `audience` field controls ticket type visibility; the promo code type controls its own access validation. These are independent concerns.

**Definition of Done:**
- [ ] `SummitTicketType` has `AUDIENCE_WITH_PROMO_CODE` constant and `isPromoCodeOnly()` helper
- [ ] Validation accepts `All`, `WithInvitation`, `WithoutInvitation`, and `WithPromoCode`
- [ ] Factory supports setting `audience` to `WithPromoCode` on create/update
- [ ] Existing ticket types with `All`, `WithInvitation`, `WithoutInvitation` continue to work unchanged
- [ ] No diagnostics errors

**Verify:**
- `php artisan clear-compiled && php artisan cache:clear`

**Review Follow-ups:**
- [x] **Constant naming deviates from SDS spec (NIT — accepted):** SDS specifies `AUDIENCE_WITH_PROMO_CODE`; implementation uses `Audience_With_Promo_Code`. Follows existing codebase convention (`Audience_All`, `Audience_With_Invitation`, `Audience_Without_Invitation`). All consumers reference the constant rather than the string literal. No correctness risk.
- [x] **`isPromoCodeOnly()` not declared in `ISummitTicketType` interface (NIT):** Method is only called on concrete `SummitTicketType` objects (via `getAllowedTicketTypes()` in the strategy), so no runtime failure. Future code working through the `ISummitTicketType` abstraction would need a cast. No current impact; worth adding to the interface in a follow-on cleanup.
- [x] **`isInviteOnlyRegistration()` ignores `WithPromoCode` types (NIT — out of scope):** A summit with only `WithPromoCode` ticket types returns `false`. Pre-existing method not changed by this task; edge case is unlikely in practice. No action required here.
- [x] **`getTicketTypeBySummit` by-ID endpoint exposes `WithPromoCode` metadata to any OAuth user (NIT — pre-existing pattern):** Requires `ReadSummitData` scope (the same scope the registration frontend uses), so any authenticated user who knows a ticket type ID can fetch its metadata. Identical behavior exists today for `WithInvitation` types. Primary public listing (`getAllBySummit`) correctly enforces `audience=All`. Not a new risk introduced by this task.

---

### Task 6: Factory, Validation Rules, and Serializers (Both New Types + Ticket Type Audience)

**Objective:** Wire both new domain-authorized types into the CRUD pipeline so they can be created/updated via API.
**Dependencies:** Task 3, Task 4, Task 5
**Mapped Scenarios:** None

**Files:**
- Modify: `app/Models/Foundation/Summit/Factories/SummitPromoCodeFactory.php` (build + populate)
- Modify: `app/Http/Controllers/Apis/Protected/Summit/Factories/Registration/PromoCodesValidationRulesFactory.php` (add + update rules)
- Create: `app/ModelSerializers/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationDiscountCodeSerializer.php`
- Create: `app/ModelSerializers/Summit/Registration/PromoCodes/DomainAuthorizedSummitRegistrationPromoCodeSerializer.php`
- Modify: `app/ModelSerializers/SerializerRegistry.php` (register both serializers)

**Key Decisions / Notes:**
- **Factory `build`:** Add cases for both ClassNames → instantiate respective classes
- **Factory `populate`:** Add cases to set `allowed_email_domains`, `quantity_per_account`, `auto_apply`. For discount variant also handle discount fields (`amount`, `rate`). Handle `allowed_ticket_types` (array of ticket type IDs) — the model's overridden `addAllowedTicketType()` adds the type via parent.
- **Validation rules** (shared across both types):
  - `allowed_email_domains` → custom validation rule: must be a JSON array of non-empty strings, where each entry matches one of the supported formats: `@domain.com` (exact domain match), `.tld` (suffix match), or `user@example.com` (exact email match). Generic `'sometimes|json'` is insufficient — it would accept malformed entries like `[123, null, ""]` that silently never match any email.
  - `quantity_per_account` → `'sometimes|integer|min:0'`
  - `auto_apply` → `'sometimes|boolean'`
  - `allowed_ticket_types` → `'sometimes|int_array'`
  - Discount variant additionally: `amount`, `rate`
- **Discount serializer:** Extends `SummitRegistrationDiscountCodeSerializer`, adds `AllowedEmailDomains`, `QuantityPerAccount`, `AutoApply` mappings. **Must RE-ADD `allowed_ticket_types`** to output (parent serializer unsets it in favor of `ticket_types_rules`). Exposes `remaining_quantity_per_account` — this value is NOT computed inside the serializer. The service layer computes it using the current member context and sets it as a transient/non-persisted value on the promo code entity before serialization.
- **Promo code serializer:** Extends `SummitRegistrationPromoCodeSerializer`, adds `AllowedEmailDomains`, `QuantityPerAccount`, `AutoApply` mappings. `allowed_ticket_types` is already included by parent. Same `remaining_quantity_per_account` transient attribute (set by service layer, not computed by serializer).
- Register both in `SerializerRegistry` with Public + CSV + PreValidation entries

**Definition of Done:**
- [ ] Can create both types via API payload with correct `class_name`
- [ ] Serializers return `allowed_email_domains`, `quantity_per_account`, `auto_apply`, `remaining_quantity_per_account`, and `allowed_ticket_types` in response
- [ ] Discount serializer also returns `ticket_types_rules`
- [ ] Validation rejects invalid payloads
- [ ] No diagnostics errors

**Verify:**
- `php artisan clear-compiled`

**Review Follow-ups:**
- [x] **`allowed_email_domains` validation is broken for natural API usage (MUST-FIX):** `getJsonPayload()` calls `Request::json()->all()` which returns an already-decoded PHP array. Laravel's `'sometimes|json'` rule requires `is_string($value)` — it returns false for a PHP array, so every real request sending `"allowed_email_domains": ["@acme.com"]` (the natural representation) is rejected with a 422. Additionally, `SummitPromoCodeFactory::populate()` calls `json_decode($data['allowed_email_domains'], true)` on what is already a PHP array — a TypeError in PHP 8 if ever reached. Fix: replace `'sometimes|json'` with a custom `AllowedEmailDomainsRule` that accepts a pre-decoded PHP array and validates each entry matches `@domain`, `.tld`, or `user@email` format (per D3 resolution plan). Also remove the `json_decode()` call from the factory — the value is already an array. Apply in both `buildForAdd` and `buildForUpdate`.
- [x] **`expand=allowed_ticket_types` silently drops field on discount variant (SHOULD-FIX):** `AbstractSerializer::_expand()` sets `$values['allowed_ticket_types']` from the expand mapping, then `SummitRegistrationDiscountCodeSerializer::serialize()` unconditionally does `unset($values['allowed_ticket_types'])`, then the child re-add guard in `DomainAuthorizedSummitRegistrationDiscountCodeSerializer::serialize()` checks `in_array('allowed_ticket_types', $relations)` — which is false when the field was requested via `?expand=`. Field disappears from the response. Fix: extend the re-add condition to also check `!empty($expand) && str_contains($expand, 'allowed_ticket_types')`.
- [x] **`json_array` is not a recognized serializer type (NIT):** Both new serializers declare `'AllowedEmailDomains' => 'allowed_email_domains:json_array'` but `AbstractSerializer` has no `case 'json_array'` in its formatter switch — the mapping is a silent NOP. Works in practice because `getAllowedEmailDomains()` returns a PHP array which the response encoder serializes correctly. Fix: rename to `json_string_array` for correctness.

---

### Task 7: Modify RegularPromoCodeTicketTypesStrategy for Audience-Based Filtering

**Objective:** Modify the ticket type strategy to handle the `WithPromoCode` audience — ticket types with this audience are excluded from public queries and only shown when a qualifying promo code includes them in `allowed_ticket_types` and the promo code is live.
**Dependencies:** Task 3, Task 4, Task 5
**Mapped Scenarios:** None

**Files:**
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/Strategies/RegularPromoCodeTicketTypesStrategy.php`

**Key Decisions / Notes:**
- In `getTicketTypes()`:
  - **Public query (no promo code):** Exclude all ticket types where `$ticketType->isPromoCodeOnly() === true`. Ticket types with other `audience` values (`All`, `WithInvitation`, `WithoutInvitation`) continue to follow their existing filtering logic.
  - **With any valid, applied promo code:**
    - A "qualifying promo code" for `WithPromoCode` ticket types is **any promo code** that includes the ticket type in its `allowed_ticket_types` and is live (`isLive()` returns true). This is NOT limited to domain-authorized or email-linked types — a plain `SummitRegistrationPromoCode` or `SummitRegistrationDiscountCode` can also unlock `WithPromoCode` ticket types. The separation of concerns is clean: `audience` controls visibility, the promo code system controls validity. There is no email validation imposed by the audience check — that is the promo code type's own concern (e.g., domain-authorized codes validate email, generic codes do not).
    - Iterate the promo code's `getAllowedTicketTypes()` collection
    - For each ticket type: add to result set regardless of its `audience` value — the promo code qualifies the user to see `WithPromoCode` types
    - Still check quantity availability (ticket type is not sold out)
    - Wrap with promo via `applyPromo2TicketType()`
  - **Ticket types with `audience = All`** continue to behave exactly as they do today — visible during their sale window, with or without a promo code
- **Key distinction from prior pre-sale approach:** Instead of bypassing `canSell()` date checks, we're filtering by `audience`. `WithPromoCode` ticket types are never visible without a promo code, regardless of dates. The promo code's `valid_since_date`/`valid_until_date` still controls when the promo code is live (and therefore when its `allowed_ticket_types` are accessible).

**Definition of Done:**
- [ ] Ticket types with `audience = WithPromoCode` are NOT returned in public queries (no promo code)
- [ ] Ticket types with `audience = WithPromoCode` ARE returned when a qualifying promo code is live and includes them in `allowed_ticket_types`
- [ ] Ticket types with `audience = All` continue to work exactly as before
- [ ] Quantity limits still respected (sold-out types not shown)
- [ ] Any promo code type (including plain generic) that includes a `WithPromoCode` ticket type in `allowed_ticket_types` and is live → ticket type IS returned
- [ ] No diagnostics errors

**Verify:**
- Unit test for strategy with audience filtering
- Test: `WithPromoCode` ticket type + no promo code → NOT returned
- Test: `WithPromoCode` ticket type + live domain-authorized promo code → IS returned
- Test: `WithPromoCode` ticket type + live generic promo code → IS returned (any type unlocks)
- Test: `All` ticket type + no promo code → IS returned (existing behavior)
- Test: `All` ticket type + promo code → IS returned with promo applied (existing behavior)

**Review Follow-ups:**
- [x] **`canBuyRegistrationTicketByType()` missing `WithPromoCode` branch — non-invited users blocked at checkout (MUST-FIX):** `Summit::canBuyRegistrationTicketByType()` (`Summit.php:5523`) has no branch for `audience = WithPromoCode`. When a user without an invitation attempts to purchase a `WithPromoCode` ticket type at checkout, `PreProcessReservationTask` (`SummitOrderService.php:1218–1235`) calls this method and receives `false` (falls through to `return $audience == SummitTicketType::Audience_Without_Invitation` at line 5571, which is `false` for `WithPromoCode`), throwing `ValidationException("Email %s can not buy registration tickets of type %s")` — the order is rejected even with a valid qualifying promo code. Fix: add `if ($audience === SummitTicketType::Audience_With_Promo_Code) return true;` immediately after the `Audience_All` branch at line 5552. Access control is already handled by the promo code's own `checkSubject()` / `canBeAppliedTo()` — the `audience` field governs visibility only, not purchase authorization.
- [x] **`canBuyRegistrationTicketByType()` missing `WithPromoCode` branch — invited users also blocked at checkout (MUST-FIX):** The same method's invitation path (`Summit.php:5555–5588`) delegates to `SummitRegistrationInvitation::isTicketTypeAllowed()` (line 5588), which only authorizes ticket types listed on the invitation — `WithPromoCode` types will not be on the invitation and are therefore rejected. An invited user trying to purchase a `WithPromoCode` ticket type hits this same dead end. The SDS states `WithPromoCode` is independent of invitation logic. The fix from the previous item (adding `return true` for `WithPromoCode` before the invitation lookup at line 5555) covers both cases.
- [x] **`WithPromoCode` types shown in listing but blocked at checkout by ticket type's own date window (SHOULD-FIX):** `RegularPromoCodeTicketTypesStrategy::getTicketTypes()` intentionally uses `isSoldOut()` (not `canSell()`) for `WithPromoCode` types (line 136), so the ticket type's own `sales_start_date`/`sales_end_date` is not checked at listing time. However, `SummitOrderService.php:904–906` enforces `canSell()` at reservation time, which includes the date-window check. A `WithPromoCode` type outside its own sale window will appear in the listing but silently fail at checkout — no useful error message. Fix: either (a) also call `canSell()` in the strategy's `WithPromoCode` loop so out-of-window types are filtered before the user sees them, or (b) confirm that `WithPromoCode` types are expected to always have their dates managed solely by the promo code's `valid_since_date`/`valid_until_date` and never have their own sale window set, in which case document this constraint explicitly.
- [x] **Strategy unit tests for audience filtering not implemented (SHOULD-FIX):** Task 7 DoD requires unit tests for 5 specific scenarios. None exist — the test file (`DomainAuthorizedPromoCodeTest.php`) only has a single `WithPromoCode` constant assertion (line 198–202). Missing tests: (1) `WithPromoCode` + no promo code → NOT returned, (2) `WithPromoCode` + live domain-authorized promo code → IS returned, (3) `WithPromoCode` + live generic promo code → IS returned, (4) `Audience_All` + no promo code → IS returned (regression), (5) `Audience_All` + promo code → IS returned with promo applied (regression).

---

### Task 8: Repository — Discovery Query and Raw SQL Joins (Both Tables)

**Objective:** Add repository method to find discoverable promo codes (domain-authorized AND existing email-linked types) matching a user's email, and add both new tables to the raw SQL joins.
**Dependencies:** Task 3, Task 4, Task 11
**Mapped Scenarios:** None

**Files:**
- Modify: `app/Repositories/Summit/DoctrineSummitRegistrationPromoCodeRepository.php`
- Modify: `app/Models/Foundation/Summit/Repositories/ISummitRegistrationPromoCodeRepository.php` (interface)

**Key Decisions / Notes:**
- New method: `getDiscoverableByEmailForSummit(Summit $summit, string $email): array`
  - Query: find all discoverable promo codes for this summit, including:
    - Domain-authorized types (`IDomainAuthorizedPromoCode`) — filtered by email domain matching at application level
    - Existing email-linked types (`MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, `SpeakerSummitRegistrationDiscountCode`) — matched by the associated member/speaker's email address
  - Return ALL email-matching codes regardless of `auto_apply` value. Domain-authorized types are matched by email domain; existing email-linked types are matched by associated member/speaker email. The `auto_apply` flag is included in the response as a frontend hint (true → apply silently, false → suggest to user) but does NOT filter results server-side. This ensures every qualifying code is discoverable on day one without requiring admins to opt in existing records.
  - If `$email` is null or empty, return empty array (no error)
- New method: `getTicketCountByMemberAndPromoCode(Member $member, SummitRegistrationPromoCode $code): int`
  - Count paid/confirmed tickets purchased by this member using this promo code
  - Used by service layer to check against `quantity_per_account`
- Update `getIdsBySummit()` raw SQL: add TWO LEFT JOINs:
  - `LEFT JOIN DomainAuthorizedSummitRegistrationDiscountCode dadc ON pc.ID = dadc.ID`
  - `LEFT JOIN DomainAuthorizedSummitRegistrationPromoCode dapc ON pc.ID = dapc.ID`
- Add BOTH types to `SQLInstanceOfFilterMapping` in `getIdsBySummit()` (lines 305-320)
- Add BOTH types to `DoctrineInstanceOfFilterMapping` in `getFilterMappings()` (lines 143-158)

**Definition of Done:**
- [ ] `getDiscoverableByEmailForSummit` returns matching codes of both domain-authorized types AND all email-linked types (regardless of `auto_apply` value)
- [ ] Returns empty array for null/empty email
- [ ] Raw SQL `$query_from` includes LEFT JOINs for both new tables
- [ ] Both ClassNames added to `SQLInstanceOfFilterMapping` and `DoctrineInstanceOfFilterMapping`
- [ ] `class_name` filter works for both new types
- [ ] No diagnostics errors

**Verify:**
- Unit test for discovery query

**Review Follow-ups:**
- [x] **Summit scoping lost in DQL OR chain (MUST-FIX):** `getDiscoverableByEmailForSummit()` at line 683 builds `->where('s.id = :summit_id')->andWhere("e INSTANCE OF A OR e INSTANCE OF B OR ...")`. Doctrine's `andWhere()` wraps existing + new conditions in an `Andx` composite that renders as `(s.id = :summit_id AND e INSTANCE OF A OR e INSTANCE OF B OR ...)`. Due to SQL/DQL operator precedence (AND before OR), only the first `INSTANCE OF` branch is summit-scoped; all remaining branches match those types from any summit, leaking cross-summit promo codes into discovery results. **Fix:** wrap the entire `INSTANCE OF` chain in an extra pair of parentheses so it is treated as a single group: `->andWhere("(e INSTANCE OF {$daDiscountClass} OR e INSTANCE OF {$daPromoClass} OR e INSTANCE OF {$memberPromoClass} OR e INSTANCE OF {$memberDiscountClass} OR e INSTANCE OF {$speakerPromoClass} OR e INSTANCE OF {$speakerDiscountClass})")`. File: `app/Repositories/Summit/DoctrineSummitRegistrationPromoCodeRepository.php`, line 687.
- [x] **Speaker email matching misses speakers without a linked Member (SHOULD-FIX):** `getDiscoverableByEmailForSummit()` at lines 711–720 guards on `$speaker->hasMember()` then accesses `$speaker->getMember()->getEmail()`. However, `PresentationSpeaker::getEmail()` (Speakers/PresentationSpeaker.php:1924) already falls through to `$this->registration_request->getEmail()` when no Member association exists. `SpeakerSummitRegistrationPromoCode::getOwnerEmail()` and `SpeakerSummitRegistrationDiscountCode::getOwnerEmail()` both call `$this->getSpeaker()->getEmail()` which uses this fallback. `SpeakerPromoCodeTrait::checkSubject()` validates via `getOwnerEmail()`. The discovery code and `checkSubject` are inconsistent: a speaker code whose speaker has only a `SpeakerRegistrationRequest` (no Member) passes checkout validation but is never returned by discovery. **Fix:** replace the `hasMember()` guard + `getMember()->getEmail()` path with a direct call to `$code->getOwnerEmail()` (which already exists on both speaker promo code types via `IOwnablePromoCode`): `$ownerEmail = $code->getOwnerEmail(); if (!empty($ownerEmail) && strtolower($ownerEmail) === $email && $code->isLive()) { $results[] = $code; }`. File: `app/Repositories/Summit/DoctrineSummitRegistrationPromoCodeRepository.php`, lines 711–719.
- [x] **`getTicketCountByMemberAndPromoCode` counts cancelled tickets (SHOULD-FIX):** The raw SQL at lines 735–742 filters by `o.Status IN ('Paid', 'Confirmed')` (order status) but does not filter by ticket status. `SummitAttendeeTicket` has its own `Status` column — `isCancelled()` at SummitAttendeeTicket.php:559 checks against `IOrderConstants::CancelledStatus`. A ticket can be individually cancelled within a paid order without changing the order status. Such cancelled tickets are still counted toward `quantity_per_account`, over-inflating the count and potentially blocking users who cancelled and want to repurchase. **Fix:** add `AND t.Status != 'Cancelled'` to the WHERE clause (or equivalently `AND t.Status = 'Paid'` if only Paid is a valid active status for tickets). The constant value is `IOrderConstants::CancelledStatus = 'Cancelled'`. File: `app/Repositories/Summit/DoctrineSummitRegistrationPromoCodeRepository.php`, line 741.

---

### Task 9: Auto-Discovery Endpoint (Route, Controller, Service)

**Objective:** Create `GET /api/v1/summits/{summit_id}/promo-codes/all/discover` endpoint that returns promo codes matching the current user's email — including both domain-authorized types and existing email-linked types (member/speaker).
**Dependencies:** Task 8, Task 11
**Mapped Scenarios:** None

**Files:**
- Modify: `routes/api_v1.php` — add route
- Modify: `app/Http/Controllers/Apis/Protected/Summit/OAuth2SummitPromoCodesApiController.php` — add `discover` action
- Modify: `app/Services/Model/Imp/SummitPromoCodeService.php` — add `discoverPromoCodes` method
- Modify: `app/Services/Model/ISummitPromoCodeService.php` — add interface method

**Key Decisions / Notes:**
- **Route:** `Route::get('all/discover', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@discover'])` inside the `promo-codes` group (line ~1952, under the `Route::group(['prefix' => 'promo-codes'])` block, inside the existing `all` sub-group at line ~1222 or as a new sub-group)
- **OAuth2 security:** Requires OAuth2 authentication with scope `SummitScopes::ReadSummitData` (`SCOPE_BASE_REALM/summits/read`). No authz groups required — any authenticated user with the read scope can discover their own qualifying codes.
- **Swagger annotation:**
  ```php
  #[OA\Get(
      path: "/api/v1/summits/{id}/promo-codes/all/discover",
      summary: "Discover qualifying promo codes for the current user",
      description: "Returns domain-authorized promo codes (matched by email domain) and existing email-linked promo codes (member/speaker, matched by associated email) for the current user",
      operationId: "discoverPromoCodesBySummit",
      tags: ["Promo Codes"],
      security: [['summit_promo_codes_oauth2' => [SummitScopes::ReadSummitData]]],
      // NO x: ['required-groups' => ...] — no authz groups needed
  )]
  ```
- Controller: get current member via `$this->resource_server_context`, call service, serialize results using `PagingResponse`. **Security: the email used for matching is always derived from the authenticated principal via `resource_server_context`. The discovery endpoint accepts no email-related query parameter and ignores any that are sent.** This prevents the endpoint from being used as an enumeration oracle (any logged-in user probing another user's qualifying codes).
- Service: call `repository->getDiscoverableByEmailForSummit($summit, $member->getEmail())`
- **QuantityPerAccount enforcement:** For each discovered code, if `quantity_per_account > 0`, count member's existing tickets with that code via `getTicketCountByMemberAndPromoCode()`. Exclude codes where count already equals `quantity_per_account` (no remaining allowance).
- **Required response fields per promo code:**
  - `class_name` — the promo code type (`DOMAIN_AUTHORIZED_DISCOUNT_CODE`, `DOMAIN_AUTHORIZED_PROMO_CODE`, `MEMBER_PROMO_CODE`, `MEMBER_DISCOUNT_CODE`, `SPEAKER_PROMO_CODE`, `SPEAKER_DISCOUNT_CODE`)
  - `auto_apply` — boolean, signals frontend whether to auto-apply
  - `remaining_quantity_per_account` — `quantity_per_account - tickets_used_count` (or `null` if `quantity_per_account` is 0/unlimited). For existing email-linked types without per-account limits, this is `null`. **Note:** `remaining_quantity_per_account` is NOT computed inside the serializer. For each discovered code, the service layer computes `remaining_quantity_per_account` using the current member context, applies discovery filtering, and sets the calculated value on the entity as a transient/non-persisted property before serialization.
  - `allowed_ticket_types` — array of ticket types this code unlocks (serialized with id, name, audience, etc.)
  - Plus standard promo code fields (`code`, `id`, etc.) and discount fields for discount variants
- **Response format:** Uses the standard `PagingResponse` envelope (same as all list endpoints) but without actual pagination. Set `total = count`, `per_page = total`, `current_page = 1`, `last_page = 1`. All results returned in a single page.
- **Multiple results / advisory only:** The discover endpoint may return multiple qualifying promo codes. No ordering or prioritization is guaranteed. Consumers MUST NOT rely on ordering and MUST explicitly decide how to handle multiple matches. The endpoint is advisory only and does not resolve conflicts between multiple qualifying promo codes.
- **Sample response:**
  ```json
  {
    "total": 4,
    "per_page": 4,
    "current_page": 1,
    "last_page": 1,
    "data": [
      {
        "id": 101,
        "class_name": "DOMAIN_AUTHORIZED_DISCOUNT_CODE",
        "code": "EARLYBIRD2026",
        "auto_apply": true,
        "quantity_per_account": 2,
        "remaining_quantity_per_account": 1,
        "allowed_email_domains": ["@acme.com", ".edu"],
        "amount": 50.00,
        "rate": 0,
        "allowed_ticket_types": [
          { "id": 10, "name": "General Admission", "cost": 200.00 },
          { "id": 11, "name": "VIP Pass", "cost": 500.00 }
        ],
        "ticket_types_rules": [
          { "id": 1, "ticket_type_id": 10, "amount": 50.00, "rate": 0 }
        ]
      },
      {
        "id": 102,
        "class_name": "DOMAIN_AUTHORIZED_PROMO_CODE",
        "code": "GOVACCESS",
        "auto_apply": false,
        "quantity_per_account": 0,
        "remaining_quantity_per_account": null,
        "allowed_email_domains": [".gov"],
        "allowed_ticket_types": [
          { "id": 10, "name": "General Admission", "cost": 200.00, "audience": "WithPromoCode" }
        ]
      },
      {
        "id": 203,
        "class_name": "SPEAKER_PROMO_CODE",
        "code": "SPK-JANE-2026",
        "auto_apply": true,
        "quantity_per_account": null,
        "remaining_quantity_per_account": null,
        "allowed_ticket_types": [
          { "id": 12, "name": "Speaker Pass", "cost": 0.00, "audience": "WithPromoCode" }
        ]
      },
      {
        "id": 304,
        "class_name": "MEMBER_DISCOUNT_CODE",
        "code": "MBR-BOB-2026",
        "auto_apply": false,
        "quantity_per_account": null,
        "remaining_quantity_per_account": null,
        "amount": 25.00,
        "rate": 0,
        "allowed_ticket_types": [
          { "id": 10, "name": "General Admission", "cost": 200.00, "audience": "All" }
        ]
      }
    ]
  }
  ```
- Security: requires authentication (current user's email is used for matching)

**Definition of Done:**
- [ ] Endpoint returns ALL email-matching promo codes (domain-authorized types + all email-linked types regardless of `auto_apply`) for authenticated user — no ordering/prioritization
- [ ] Each result includes `class_name`, `auto_apply`, `remaining_quantity_per_account`, and `allowed_ticket_types`
- [ ] `remaining_quantity_per_account` is correctly calculated per member
- [ ] Returns empty array if no codes match
- [ ] Returns empty array if user's email is null/empty (no error)
- [ ] Codes with exhausted `quantity_per_account` are excluded from results
- [ ] Returns 403 if not authenticated
- [ ] Controller does not read email from request input; email is always derived from `resource_server_context`
- [ ] No diagnostics errors

**Verify:**
- Integration test calling the endpoint

**Review Follow-ups:**

- [x] **`remaining_quantity_per_account` absent from member/speaker serializer output (MUST-FIX):**
  All four member/speaker serializers (`MemberSummitRegistrationPromoCodeSerializer`, `MemberSummitRegistrationDiscountCodeSerializer`, `SpeakerSummitRegistrationPromoCodeSerializer`, `SpeakerSummitRegistrationDiscountCodeSerializer`) do not output `remaining_quantity_per_account`. The DoD requires every discover result to include this field, and the SDS sample response shows `"remaining_quantity_per_account": null` for `MEMBER_DISCOUNT_CODE` and `SPEAKER_PROMO_CODE`. The domain-authorized serializers correctly set it from a transient property; member/speaker serializers must emit `null` unconditionally (these types have no per-account limit concept).
  **Fix:** In the `serialize()` override of each of the four member/speaker serializers, add `$values['remaining_quantity_per_account'] = null;` before returning `$values`. No entity change required — member/speaker entities do not need a transient property; the value is always `null` for these types.

- [x] **`allowed_ticket_types` absent from member/speaker discount code responses (MUST-FIX):**
  `SummitRegistrationDiscountCodeSerializer::serialize()` unconditionally calls `unset($values['allowed_ticket_types'])` (line 46). `MemberSummitRegistrationDiscountCodeSerializer` and `SpeakerSummitRegistrationDiscountCodeSerializer` both extend this class and never re-add the key, so `MEMBER_DISCOUNT_CODE` and `SPEAKER_DISCOUNT_CODE` results from the discover endpoint are missing `allowed_ticket_types`. `DomainAuthorizedSummitRegistrationDiscountCodeSerializer` already demonstrates the correct fix pattern at lines 47–56: check `in_array('allowed_ticket_types', $relations)` and rebuild the array from `$code->getAllowedTicketTypes()`.
  **Fix:** In `MemberSummitRegistrationDiscountCodeSerializer::serialize()` and `SpeakerSummitRegistrationDiscountCodeSerializer::serialize()`, after calling `parent::serialize()`, re-add `allowed_ticket_types` using the same pattern as `DomainAuthorizedSummitRegistrationDiscountCodeSerializer.php:47–56`. The controller's default `$relations` already includes `'allowed_ticket_types'`, so no controller change is needed.

- [x] **`IDomainAuthorizedPromoCode` interface missing `setRemainingQuantityPerAccount` / `getRemainingQuantityPerAccount` declarations (SHOULD-FIX):**
  `SummitPromoCodeService::discoverPromoCodes()` narrows a code to `IDomainAuthorizedPromoCode` via `instanceof`, then calls `$code->setRemainingQuantityPerAccount(...)` (service lines 1035, 1037). The interface (`IDomainAuthorizedPromoCode.php`) declares only `getAllowedEmailDomains()`, `getQuantityPerAccount()`, and `matchesEmailDomain()` — neither setter nor getter is declared. PHP resolves the call dynamically at runtime (both concrete classes `DomainAuthorizedSummitRegistrationPromoCode` and `DomainAuthorizedSummitRegistrationDiscountCode` implement both methods), but static analysis tools (PHPStan/Psalm) will flag this as a call on an undefined method of the interface type.
  **Fix:** Add `public function setRemainingQuantityPerAccount(?int $remaining): void;` and `public function getRemainingQuantityPerAccount(): ?int;` to `IDomainAuthorizedPromoCode.php`. Both concrete classes already implement these methods, so no implementation change is needed — only the interface declaration.

---

### Task 10: QuantityPerAccount Checkout Enforcement

**Objective:** Enforce `quantity_per_account` during order checkout — reject orders that would exceed the per-account limit for domain-authorized promo codes.
**Dependencies:** Task 3, Task 4, Task 8
**Mapped Scenarios:** None

**Files:**
- Modify: `app/Services/Model/Imp/SummitOrderService.php` — `PreProcessReservationTask` class

**Key Decisions / Notes:**
- In `PreProcessReservationTask::run()` (around line 995-1028), after validating the promo code with `canBeAppliedTo()` and `getMaxUsagePerOrder()`:
  - Check if the promo code `instanceof IDomainAuthorizedPromoCode`
  - If yes AND `quantity_per_account > 0`:
    - Count existing tickets purchased by the current member (order owner) with this promo code via `getTicketCountByMemberAndPromoCode()` (from Task 8)
    - Add the count of tickets being ordered in THIS order for this promo code
    - If total > `quantity_per_account`, throw `ValidationException` with message like "Promo code {code} has reached the maximum of {limit} tickets per account."
  - The repository method needs to be injected/available in `PreProcessReservationTask` — follow the existing pattern of how `$this->ticket_type_repository` is used in that class
- **Concurrency strategy:** The quantity check and order creation must be race-safe. Use a pessimistic row lock on the promo code entity within the existing `ITransactionService::transaction()` boundary: `SELECT ... FOR UPDATE` on the promo code row before counting tickets and creating the order. This prevents two concurrent checkouts by the same user (e.g., two browser tabs) from both reading `count = limit-1` and both succeeding. The lock is held only for the duration of the order transaction, so contention is limited to concurrent uses of the same promo code.
- This is the second enforcement point (after discovery filtering in Task 9). Both are needed — discovery is advisory (UX), checkout is authoritative (prevents abuse if frontend is bypassed).

**Definition of Done:**
- [ ] Order with domain-authorized promo code is rejected when existing tickets + new order tickets would exceed `quantity_per_account` (i.e., total > limit, not >=)
- [ ] Order is allowed when member is still under the limit
- [ ] `quantity_per_account = 0` means unlimited (no enforcement)
- [ ] Non-domain-authorized promo codes are not affected
- [ ] Concurrent checkouts by the same member cannot exceed `quantity_per_account` (pessimistic lock via `SELECT ... FOR UPDATE` within `ITransactionService::transaction()`)
- [ ] No diagnostics errors

**Verify:**
- Unit test: order with exhausted quantity_per_account → ValidationException
- Unit test: order within limit → succeeds
- Integration test: concurrent checkouts by same member cannot exceed limit

**Review Follow-ups:**
- None

---

### Task 11: Auto-Apply Support for Existing Email-Linked Promo Codes

**Objective:** Apply the `AutoApplyPromoCodeTrait` (from Task 2) to the four existing email-linked promo code types and wire them into the discovery pipeline.
**Dependencies:** Task 1, Task 2
**Mapped Scenarios:** None

**Files:**
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/MemberSummitRegistrationPromoCode.php` — add `use AutoApplyPromoCodeTrait`
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/MemberSummitRegistrationDiscountCode.php` — add `use AutoApplyPromoCodeTrait`
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/SpeakerSummitRegistrationPromoCode.php` — add `use AutoApplyPromoCodeTrait`
- Modify: `app/Models/Foundation/Summit/Registration/PromoCodes/SpeakerSummitRegistrationDiscountCode.php` — add `use AutoApplyPromoCodeTrait`
- Modify: `app/Models/Foundation/Summit/Factories/SummitPromoCodeFactory.php` — handle `auto_apply` in populate for member/speaker types
- Modify: `app/Http/Controllers/Apis/Protected/Summit/Factories/Registration/PromoCodesValidationRulesFactory.php` — add `auto_apply` validation rule for member/speaker types
- Modify: serializers for member/speaker promo code types — expose `auto_apply` field

**Key Decisions / Notes:**
- Each of the four existing types adds `use AutoApplyPromoCodeTrait;` — this maps the `AutoApply` column on their respective joined tables (added in Task 1 migration) to the `$auto_apply` property via ORM annotations on the trait.
- The base `SummitRegistrationPromoCode` class is NOT modified — `auto_apply` is a per-subtype concern, not a base class concern.
- **Existing email-linked types that participate in discovery:**
  - `MemberSummitRegistrationPromoCode` — associated with a `Member` via `$owner` relationship
  - `MemberSummitRegistrationDiscountCode` — associated with a `Member` via `$owner` relationship
  - `SpeakerSummitRegistrationPromoCode` — associated with a `PresentationSpeaker` which has a `Member`
  - `SpeakerSummitRegistrationDiscountCode` — associated with a `PresentationSpeaker` which has a `Member`
- The discovery endpoint (Task 9) matches these types by checking `$code->getOwner()->getEmail() === $currentUserEmail` (for member types) or `$code->getSpeaker()->getMember()->getEmail() === $currentUserEmail` (for speaker types).
- **Factory `populate`:** Add `auto_apply` handling for `MEMBER_PROMO_CODE`, `MEMBER_DISCOUNT_CODE`, `SPEAKER_PROMO_CODE`, `SPEAKER_DISCOUNT_CODE` class names in the factory's populate method.
- **Validation rules:** Add `'auto_apply' => 'sometimes|boolean'` to validation rules for all four existing email-linked types.

**Definition of Done:**
- [ ] All four existing types use `AutoApplyPromoCodeTrait`
- [ ] `AutoApply` column on each subtype's joined table is mapped via the trait's ORM annotations
- [ ] Existing member/speaker promo codes can have `auto_apply` set via API
- [ ] Serializers for member/speaker types expose `auto_apply`
- [ ] All existing promo codes default to `auto_apply = false` (no behavioral change)
- [ ] Base `SummitRegistrationPromoCode` class is NOT modified
- [ ] No diagnostics errors

**Verify:**
- API test: verify a speaker promo code is returned in discovery when email matches, with correct `auto_apply` value in response

**Review Follow-ups:**
- None

---

### Task 12: Unit Tests

**Objective:** Comprehensive test coverage for domain matching, audience-based filtering, collision avoidance, checkout enforcement, discovery (including existing email-linked types), and auto-apply behavior.
**Dependencies:** Task 2, Task 3, Task 4, Task 5, Task 7, Task 8, Task 9, Task 10, Task 11
**Mapped Scenarios:** None

**Files:**
- Create: `tests/Unit/Services/DomainAuthorizedPromoCodeTest.php`

**Key Decisions / Notes:**
- Test domain matching logic:
  - `@acme.com` matches `user@acme.com`, rejects `user@other.com`
  - `.edu` matches `user@mit.edu`, `user@cs.stanford.edu`, rejects `user@acme.com`
  - `.gov` matches `user@agency.gov`
  - `specific@email.com` matches exact email only
  - Case insensitivity: `@ACME.COM` matches `user@acme.com`
  - Empty domains array → passes all
  - Multiple patterns → matches if any match
- Test `checkSubject` throws for non-matching emails
- Test ticket type audience filtering:
  - `WithPromoCode` ticket type + no promo code → NOT returned by strategy
  - `WithPromoCode` ticket type + live domain-authorized promo code → IS returned
  - `WithPromoCode` ticket type + live generic (plain) promo code → IS returned (any type unlocks)
  - `All` ticket type + no promo code → IS returned (existing behavior unchanged)
  - `All` ticket type + promo code → IS returned with promo applied (existing behavior)
- Test collision avoidance (discount variant):
  - `addTicketTypeRule` rejects rules for types not in `allowed_ticket_types`
  - `addTicketTypeRule` does NOT modify `allowed_ticket_types`
  - `removeTicketTypeRuleForTicketType` does NOT modify `allowed_ticket_types`
- Test `auto_apply` field serialization for both domain-authorized types AND existing email-linked types
- Test `remaining_quantity_per_account` calculated attribute in serializer
- Test discovery returns domain-authorized types (matched by email domain)
- Test discovery returns existing email-linked types matched by member email regardless of `auto_apply` value
- Test discovery returns `auto_apply` flag accurately in response (true/false per code) for frontend to branch on
- Test `canBeAppliedTo` override on discount variant:
  - Domain-authorized discount code + free `WithPromoCode` ticket type → `canBeAppliedTo` returns true
  - Domain-authorized discount code + paid `All` ticket type → `canBeAppliedTo` returns true (normal discount behavior)
  - End-to-end: admin creates discount code → adds free `WithPromoCode` Speaker Pass to `allowed_ticket_types` → speaker hits discovery → auto-apply → checkout succeeds with $0 line item
- Test discovery endpoint security:
  - Discovery uses authenticated principal's email, not query parameters
  - `?email=other@user.com` is ignored; results reflect authenticated user only
- Test `QuantityPerAccount` enforcement at discovery (exclude exhausted codes)
- Test `QuantityPerAccount` enforcement at checkout (reject over-limit orders)
- Test `QuantityPerAccount` concurrent checkout enforcement (two simultaneous checkouts by same member cannot both succeed when only one slot remains)

**Definition of Done:**
- [ ] All tests pass
- [ ] Domain matching edge cases covered
- [ ] Audience-based ticket type filtering tested
- [ ] Collision avoidance tested (discount variant only)
- [ ] Auto-apply field tested for domain-authorized and existing email-linked types
- [ ] Discovery includes both domain-authorized and email-linked types
- [ ] Checkout enforcement tested
- [ ] No diagnostics errors

**Verify:**
- `php artisan test --filter=DomainAuthorizedPromoCodeTest`

**Review Follow-ups:**
- None

## Resolved Decisions

1. **Explicit audience model (replaces pre-sale date-window approach):** Stakeholders decided that ticket types intended for promo-code-only distribution should be explicitly marked with `audience = WithPromoCode` rather than relying on date-window tricks. This is clearer for admins and simpler to implement. `WithPromoCode` ticket types are never visible without a qualifying promo code.
2. **Both discount and promo code variants:** Both `DomainAuthorizedSummitRegistrationDiscountCode` (with discount) and `DomainAuthorizedSummitRegistrationPromoCode` (access-only) are needed. Shared logic via trait.
3. **Auto-apply via trait, not base class:** `auto_apply` boolean is provided by a dedicated `AutoApplyPromoCodeTrait` with per-subtype `AutoApply` columns on joined tables — NOT on the base `SummitRegistrationPromoCode` class. This keeps the concern scoped to only the types that participate in discovery (domain-authorized types and existing email-linked types). Lead engineer decision: adding a column to the base class would be adding a concern to a class that shouldn't own it.
4. **QuantityPerAccount enforcement:** Dual enforcement — (1) Discovery time: exclude exhausted codes from results + expose `remaining_quantity_per_account` calculated attribute in serializer. (2) Checkout time: `PreProcessReservationTask` in `SummitOrderService.php` rejects orders exceeding the limit. Discovery is advisory (UX), checkout is authoritative (prevents abuse).
5. **Collision avoidance (discount variant):** Override `addTicketTypeRule()` and `removeTicketTypeRuleForTicketType()` to prevent the parent's dual-write from corrupting `allowed_ticket_types`. `addTicketTypeRule()` requires the type to already be in `allowed_ticket_types`. The promo code variant has no collision (base class has no `addTicketTypeRule()`).
6. **Audience filtering lives in the strategy:** `RegularPromoCodeTicketTypesStrategy` handles filtering out `WithPromoCode` ticket types from public queries and including them when a qualifying promo code is present.
7. **Existing email-linked promo codes participate in discovery:** `MemberSummitRegistrationPromoCode`, `MemberSummitRegistrationDiscountCode`, `SpeakerSummitRegistrationPromoCode`, and `SpeakerSummitRegistrationDiscountCode` gain `auto_apply` support and are returned by the discovery endpoint when matched by associated member/speaker email — regardless of `auto_apply` value. The `auto_apply` flag is a frontend hint (true → apply silently, false → suggest to user), not a server-side filter. This means speakers and members are discoverable on day one without admin opt-in; the frontend decides how to present them.
8. **"Qualifying promo code" means any promo code type:** A `WithPromoCode` ticket type is unlocked by any promo code (domain-authorized, email-linked, or plain generic) that includes it in `allowed_ticket_types` and is live. The `audience` field controls visibility; the promo code type independently controls its own access validation (e.g., domain-authorized codes validate email domains, generic codes do not). These are separate concerns — there is no type restriction on which promo codes can unlock `WithPromoCode` ticket types.
9. **This SDS is API-only (summit-api):** Frontend changes for `summit-admin` and `summit-registration-lite` require separate companion SDSs.

## Configuration

N/A — No new environment variables, config files, or feature flags are required. All new behavior is driven by data (promo code types, ticket type audience values) managed through the existing admin API. The `auto_apply` field defaults to `false`, so no existing behavior changes without explicit admin action.

## Audit/Logging Integration

N/A — This feature does not introduce new audit events or logging beyond what the existing promo code and order pipelines already provide. Promo code application and order creation are already logged through the standard OTLP pipeline. The new discovery endpoint is a read-only query and does not require audit logging.

## Rollout Plan

No phased rollout or feature flags are required. The changes are additive and backwards-compatible:
- New promo code subtypes are only created when admins explicitly use the new `class_name` values
- The `WithPromoCode` audience value is only applied when admins explicitly set it on a ticket type
- `auto_apply` defaults to `false` on all existing records (migration adds column with default)
- The discovery endpoint is new and has no existing consumers
- **Rollback:** If issues arise, the migration can be reversed (`down` method drops the new tables, removes the ENUM value, and drops the `AutoApply` columns). No data loss for existing records since all changes are additive.

## Deferred Ideas

- CSV import/export support for domain-authorized codes
- Bulk domain pattern management endpoint
- Companion SDS for `summit-admin` (admin UI for managing domain-authorized codes, audience toggle, auto-apply settings)
- Companion SDS for `summit-registration-lite` (registration frontend for auto-discovery UX, promo-code-only ticket type display)
