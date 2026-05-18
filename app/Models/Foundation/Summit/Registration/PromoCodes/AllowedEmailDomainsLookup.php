<?php namespace models\summit;
/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

/**
 * Class AllowedEmailDomainsLookup
 * @package models\summit
 *
 * Immutable per-request DTO used by discovery / matching paths to avoid
 * recomputing strtolower(trim(...)) for every allowed-email-domain pattern
 * on every candidate code. Partitions normalized patterns into:
 *
 *  - $exactSet:    O(1) lookup map for "@domain.tld" and "user@domain.tld" patterns
 *  - $suffixList:  array of ".tld"-style suffixes for endsWith / str_ends_with checks
 *  - $patternsHash: stable sha1 over the sorted normalized pattern set; identifies
 *                   the pattern set for change detection / equality comparisons.
 *  - $unrestricted: true iff the input pattern array was empty. Distinguishes
 *                   "no patterns configured" (legacy "no restriction") from
 *                   "patterns configured but all malformed" (which the legacy
 *                   matcher treats as no match — see
 *                   DomainAuthorizedPromoCodeTrait::matchesEmailDomain parity contract).
 */
final class AllowedEmailDomainsLookup
{
    public function __construct(
        public readonly array $exactSet,
        public readonly array $suffixList,
        public readonly string $patternsHash,
        public readonly bool $unrestricted
    ) {
    }
}
