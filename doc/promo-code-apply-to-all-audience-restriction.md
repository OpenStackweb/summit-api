# Promo Code "Apply to All" Audience Restriction — SDS

Created: 2026-05-11
Author: casey@caseylocker.com
Status: PENDING
Type: Change Request (bug fix / hardening)
ClickUp: [86b9vrpxp](https://app.clickup.com/t/86b9vrpxp)
Parent feature: [86b952pgc](https://app.clickup.com/t/86b952pgc) — Promo Codes for Early Registration (merged via PR #525, commit `3967dddf0`)
Parent SDS: `doc/promo-codes-for-early-registration-access.md`

## Summary

**Goal:** When a promo code's `allowed_ticket_types` collection is empty (the "apply to all ticket types" path), restrict applicability to ticket types whose `audience = All`. Ticket types with `audience = WithInvitation`, `WithoutInvitation`, or `WithPromoCode` must NOT be matched by the implicit sweep — they must be opted into explicitly via `allowed_ticket_types`.

**Why:** With the introduction of the `WithPromoCode` audience by the parent feature, the existing "empty `allowed_ticket_types` matches everything" behavior in `SummitRegistrationPromoCode::canBeAppliedTo()` becomes a leak: a discount code created with no per-ticket-type rules currently applies to `WithPromoCode` ticket types as well, exposing tickets that are deliberately hidden from the public. Parent feature SDS Resolved Decision #8 — *"audience controls visibility, type controls access"* — is violated unless the implicit branch is tightened.

**Approach:** Single load-bearing edit to `SummitRegistrationPromoCode::canBeAppliedTo()` so the `allowed_ticket_types->count() === 0` branch returns `true` only when `$ticketType->getAudience() === SummitTicketType::Audience_All`. All four callers in the application (storefront strategy, two order-pipeline gates, and the validation strategy) flow through this method; subclass overrides (`SummitRegistrationDiscountCode`, `DomainAuthorizedSummitRegistrationDiscountCode`) delegate to the base for the membership check and are unaffected by the new audience constraint they don't reach.

## Scope

### In Scope

- Tighten `SummitRegistrationPromoCode::canBeAppliedTo()` (`app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php:483-495`) so the empty-collection branch additionally requires `Audience = All`.
- New PHPUnit test cases extending the existing audience-filtering test suite (`tests/Unit/Services/DomainAuthorizedPromoCodeTest.php` or a new dedicated file) covering the audience × allowed_ticket_types matrix.
- Audit of all direct callers of `canBeAppliedTo()` and any code paths that independently treat empty `allowed_ticket_types` as "applies to all" — confirm the centralized fix is sufficient.

### Out of Scope

- Frontend label / helper-text copy changes — covered by companion `summit-admin` spec `docs/superpowers/specs/2026-05-11-promo-code-apply-to-all-audience-restriction.md`.
- Data migration / backfill for existing discount codes that today implicitly cover `WithoutInvitation` or `WithInvitation` tickets. Per the change request, the behavior change is intentional; no migration is required.
- Changes to `SummitAttendee.php:1367` invitation-side empty-collection check (`getAllowedTicketTypes()->count() === 0 && getAllowedBadgeFeatureTypes()->count() === 0`) — different domain (invitations), out of scope.

### Companion SDSs

- **`summit-admin`**: `docs/superpowers/specs/2026-05-11-promo-code-apply-to-all-audience-restriction.md` — i18n label update + helper-text row beneath the "Apply to all Ticket Types" checkbox in `discount-base-pc-form.js`. No JS logic change required; this BE fix is the load-bearing enforcement.

## Truths (Authoritative Decisions)

1. **Empty `allowed_ticket_types` ≠ "every audience".** When `allowed_ticket_types->count() === 0`, `canBeAppliedTo()` must only return `true` for ticket types with `audience = All`. For `WithInvitation`, `WithoutInvitation`, and `WithPromoCode`, the only path to `true` is explicit membership in `allowed_ticket_types`.

2. **Centralize at `SummitRegistrationPromoCode::canBeAppliedTo()`.** All four production callers — `RegularPromoCodeTicketTypesStrategy::applyPromo2TicketType()`, `SummitOrderService.php:812`, `SummitOrderService.php:1093`, and `RegularTicketTypePromoCodeValidationStrategy.php:91` — invoke the method on the runtime entity. Subclass overrides (`SummitRegistrationDiscountCode::canBeAppliedTo()` line 232, `DomainAuthorizedSummitRegistrationDiscountCode::canBeAppliedTo()` line 145) delegate to the base via `parent::canBeAppliedTo()` / `SummitRegistrationPromoCode::canBeAppliedTo()`. A single edit at the base level cascades through everything without touching the overrides.

3. **`DomainAuthorizedSummitRegistrationDiscountCode` is unaffected.** Its override operates exclusively on the `count() > 0` path (membership check), with an explicit pre-validation in `addTicketTypeRule()` that requires the ticket type to already be in `allowed_ticket_types`. The audience restriction in the empty branch is logically unreachable for this subtype under normal operation.

4. **No new field, no flag, no migration.** The `apply_to_all_tix` checkbox in `summit-admin` is and remains a UI-only field — it is deleted from the payload before send (`summit-admin/src/actions/promocode-actions.js:160`) and rederived from `ticket_types_rules.length === 0` on receive. The backend has never had an explicit "apply to all" flag and won't gain one. Enforcement is purely the audience filter on the implicit branch.

5. **Behavioral change is intentional and accepted.** Existing discount codes that today implicitly covered `WithoutInvitation` ticket types will stop covering them after this change. The change request explicitly accepts this: *"Existing discount codes with 'Apply to all' checked continue to work correctly for Audience = All ticket types — no regression."* No backfill is provided.

6. **Audience storage is the column-backed string field.** `SummitTicketType::$audience` (column `Audience`, `app/Models/Foundation/Summit/Registration/SummitTicketType.php:154`) is the source of truth. Constants `Audience_All = 'All'`, `Audience_With_Invitation = 'WithInvitation'`, `Audience_Without_Invitation = 'WithoutInvitation'`, `Audience_With_Promo_Code = 'WithPromoCode'` (lines 58-67). Comparison must be by constant, not by string literal.

## Approach

**Chosen:** Single-line tightening of the empty-collection branch in `SummitRegistrationPromoCode::canBeAppliedTo()`.

**Why:** The method is the central choke point. Tightening it here:

- Propagates through both subclass overrides without modification (each delegates to the base for the membership / empty check).
- Affects every production code path that gates ticket-type applicability on a promo code: storefront listing wrap (`RegularPromoCodeTicketTypesStrategy::applyPromo2TicketType()`), order checkout (`SummitOrderService.php:812,1093`), and direct validation (`RegularTicketTypePromoCodeValidationStrategy.php:91`).
- Aligns with the parent feature's already-established storefront filter at `RegularPromoCodeTicketTypesStrategy::getTicketTypes():113`, which seeds the public list with `Audience_All` only. The base-class tightening closes the back-door at the discount/order-pipeline path that does not flow through `getTicketTypes()`.

**Alternatives considered:**

- *Add an explicit `apply_to_all_audience_all_only` flag to the entity.* Rejected — no admin UI affordance exists to set it, the FE flag is UI-only and never reaches the BE, and a stored flag would diverge from the FE's derive-from-collection model. Higher risk, no behavioral gain.
- *Filter in each caller instead of the base method.* Rejected — duplicates the same check in four places, and risks future call sites bypassing the rule. Centralization is correct.
- *Backfill `allowed_ticket_types` for existing discount codes that previously matched `WithoutInvitation` tickets.* Rejected per Truth #5.

## Context for Implementer

- **Entry point:** `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php:483-495` is the only production file that changes. Test file is `tests/Unit/Services/DomainAuthorizedPromoCodeTest.php` (extend) or a new file in the same folder.
- **Audience constants:** `app/Models/Foundation/Summit/Registration/SummitTicketType.php:58-67`. Reference by constant (`SummitTicketType::Audience_All`), not the string literal `'All'`.
- **Existing test pattern:** `tests/Unit/Services/DomainAuthorizedPromoCodeTest.php` already uses PHPUnit mocks for `SummitTicketType` via `buildMockTicketType(int $id, string $audience, bool $canSell = true)` (line 276) — the new tests should follow the same shape (pure unit tests, no DB).
- **Subclass interaction:** `SummitRegistrationDiscountCode::canBeAppliedTo()` (line 232) adds a free-ticket guard then `return parent::canBeAppliedTo($ticketType)`. `DomainAuthorizedSummitRegistrationDiscountCode::canBeAppliedTo()` (line 145) bypasses the free-ticket guard and calls `SummitRegistrationPromoCode::canBeAppliedTo()` directly. Both flow through the patched base.
- **Patterns to follow:** Read `process/sds-standards.md` and `patterns/software-design-patterns.md` in the fn-skills vault before implementing — relevant principles: *Reuse Existing Abstractions Before Adding New Ones* (no new field), *Separate Visibility from Applicability* (this fix preserves it).

## Tasks

### Task 1: Tighten `SummitRegistrationPromoCode::canBeAppliedTo()`

**File:** `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php`

**Change:** Replace the unconditional `return true;` in the empty-collection branch with `return $ticketType->getAudience() === SummitTicketType::Audience_All;`. Keep the existing membership-check branch unchanged. (After the edit the return lands at line 494 with a short comment block above it.)

**Required `use` statement check:** `SummitTicketType` is already imported via the type-hint on the method signature (PHP imports it). Verify no additional `use` statement is required.

**Definition of Done:**

- [ ] Empty `allowed_ticket_types` + ticket `Audience = All` → method returns `true`.
- [ ] Empty `allowed_ticket_types` + ticket `Audience = WithInvitation` → method returns `false`.
- [ ] Empty `allowed_ticket_types` + ticket `Audience = WithoutInvitation` → method returns `false`.
- [ ] Empty `allowed_ticket_types` + ticket `Audience = WithPromoCode` → method returns `false`.
- [ ] Non-empty `allowed_ticket_types` containing the ticket id → method returns `true` (regardless of audience).
- [ ] Non-empty `allowed_ticket_types` not containing the ticket id → method returns `false`.
- [ ] `SummitRegistrationDiscountCode::canBeAppliedTo()` and `DomainAuthorizedSummitRegistrationDiscountCode::canBeAppliedTo()` still work as before for the `count() > 0` membership branch.

### Task 2: PHPUnit test matrix

**File:** `tests/Unit/Services/PromoCodeCanBeAppliedToAudienceTest.php` (new — keep this concern isolated from the existing `DomainAuthorizedPromoCodeTest.php` so the test name describes the bug we're guarding).

**Tests:**

- `testEmptyAllowedTicketTypesAndAudienceAllReturnsTrue`
- `testEmptyAllowedTicketTypesAndAudienceWithInvitationReturnsFalse`
- `testEmptyAllowedTicketTypesAndAudienceWithoutInvitationReturnsFalse`
- `testEmptyAllowedTicketTypesAndAudienceWithPromoCodeReturnsFalse`
- `testNonEmptyAllowedTicketTypesContainingTicketReturnsTrueRegardlessOfAudience` (data-provider over four audiences)
- `testNonEmptyAllowedTicketTypesNotContainingTicketReturnsFalse`
- `testDomainAuthorizedDiscountCodeStillAppliesToExplicitAllowedTicketTypes` (smoke test that the override still works — uses a ticket type added via `addAllowedTicketType()` and asserts `canBeAppliedTo()` returns `true` regardless of audience).

**Run:** `vendor/bin/phpunit tests/Unit/Services/PromoCodeCanBeAppliedToAudienceTest.php`

**Definition of Done:**

- [ ] All 7+ test methods pass.
- [ ] Full existing PHPUnit suite under `tests/Unit/Services/` passes — no regression in `DomainAuthorizedPromoCodeTest` or `SummitPromoCodeServiceDiscoveryTest`.

### Task 3: Caller audit

Search the codebase for any direct empty-collection check on promo-code `allowed_ticket_types` that bypasses `canBeAppliedTo()`. Confirm there are none, or fix any found. Out-of-scope sibling: `SummitAttendee.php:1367` (invitation domain).

**Search commands (record in PR description):**

```bash
grep -rn 'allowed_ticket_types->count\|getAllowedTicketTypes()->count\|getAllowedTicketTypes()->isEmpty' app/ --include="*.php"
grep -rn 'canBeAppliedTo' app/ --include="*.php"
```

**Definition of Done:**

- [ ] All callers of `canBeAppliedTo()` enumerated in PR description.
- [ ] No additional code path independently treats empty `allowed_ticket_types` as "apply to all" for promo codes.

## Test Plan (Manual / Integration)

Run after merge in a staging summit with at least one ticket type of each audience value:

1. Create a `SUMMIT_DISCOUNT_CODE` with no `ticket_types_rules` (i.e., "Apply to all" path) and `rate = 10`.
2. As a registration user, list ticket types — `WithPromoCode` and `WithInvitation` types must NOT show a discounted price for this code.
3. Submit a checkout for an `Audience = All` ticket type with the code — discount applies.
4. Submit a checkout for an `Audience = WithoutInvitation` ticket type with the same code — code must NOT apply (rejection or full price, depending on the existing pipeline behavior).
5. Add the `WithPromoCode` ticket type to the code via `allowed_ticket_types` (explicit opt-in). Re-list — the ticket type now appears as available through the code and the discount applies on checkout.

## Acceptance Criteria

- [ ] Tightened `canBeAppliedTo()` deployed; unit-test matrix green.
- [ ] No existing test breaks; full PHPUnit suite passes.
- [ ] Manual test plan (above) passes against staging.
- [ ] Companion summit-admin spec merged and label updated so admins can see the new scope wording.

## Open Questions

None. All semantic decisions are settled by the parent feature SDS Resolved Decision #8 and the change-request answers captured in Truths #1, #4, #5.

## References

- Parent feature SDS: `doc/promo-codes-for-early-registration-access.md`
- Parent feature PR: #525 (`3967dddf0`)
- ClickUp change request: 86b9vrpxp
- Companion admin spec: `summit-admin/docs/superpowers/specs/2026-05-11-promo-code-apply-to-all-audience-restriction.md`
- Audience enum: `app/Models/Foundation/Summit/Registration/SummitTicketType.php:58-67`
- Method under change: `app/Models/Foundation/Summit/Registration/PromoCodes/SummitRegistrationPromoCode.php:483-495`
