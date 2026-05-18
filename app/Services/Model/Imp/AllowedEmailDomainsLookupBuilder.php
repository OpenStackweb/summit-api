<?php namespace App\Services\Model;
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

use models\summit\AllowedEmailDomainsLookup;

/**
 * Class AllowedEmailDomainsLookupBuilder
 * @package App\Services\Model
 *
 * Pure builder: turns a raw list of allowed-email-domain patterns into an
 * immutable AllowedEmailDomainsLookup DTO. No DB, no cache, no Doctrine.
 *
 * Normalization rules:
 *   - strtolower(trim((string) $raw)) on every pattern
 *   - drop empty values
 *   - case-insensitive dedup (first occurrence wins)
 *
 * Partition rules:
 *   - leading '@'                   -> exactSet[$p] = true   (e.g. "@acme.com")
 *   - leading '.'                   -> suffixList[]  = $p    (e.g. ".edu")
 *   - contains '@' (not at start)   -> exactSet[$p] = true   (e.g. "user@acme.com")
 *   - otherwise                     -> dropped silently
 *
 * patternsHash = sha1(implode('|', $sortedNormalizedPatterns)) — stable
 * regardless of input order, so callers can use it for change detection / equality.
 */
final class AllowedEmailDomainsLookupBuilder
{
    public function build(array $patterns): AllowedEmailDomainsLookup
    {
        // Capture "no patterns configured" from the RAW input BEFORE any
        // normalization or partitioning. Required for legacy parity: a
        // non-empty input whose patterns all drop as malformed must NOT
        // be treated as unrestricted.
        $unrestricted = count($patterns) === 0;

        $exactSet   = [];
        $suffixList = [];
        $seen       = [];
        $normalized = [];

        foreach ($patterns as $raw) {
            $p = strtolower(trim((string) $raw));
            if ($p === '') {
                continue;
            }
            if (isset($seen[$p])) {
                continue;
            }
            $seen[$p] = true;

            $first = $p[0];
            if ($first === '@') {
                $exactSet[$p] = true;
                $normalized[] = $p;
                continue;
            }
            if ($first === '.') {
                $suffixList[] = $p;
                $normalized[] = $p;
                continue;
            }
            if (strpos($p, '@') !== false) {
                $exactSet[$p] = true;
                $normalized[] = $p;
                continue;
            }
            // otherwise: dropped silently
        }

        sort($normalized);
        $patternsHash = sha1(implode('|', $normalized));

        return new AllowedEmailDomainsLookup($exactSet, $suffixList, $patternsHash, $unrestricted);
    }
}
