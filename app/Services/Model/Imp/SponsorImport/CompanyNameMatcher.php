<?php namespace App\Services\Model\Imp\SponsorImport;

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
 * Class CompanyNameMatcher
 * Resolves a CSV company name against existing companies: exact first, then by normalized key.
 * @package App\Services\Model\Imp\SponsorImport
 */
final class CompanyNameMatcher
{
    private const LegalSuffixes = [
        'inc', 'incorporated', 'llc', 'corp', 'corporation', 'co', 'company', 'ltd', 'limited',
        'gmbh', 'ag', 'sa', 'plc', 'bv', 'nv', 'pte', 'pty', 'srl', 'spa', 'kk', 'lp', 'llp',
    ];

    /**
     * @var array<string, array{id:int,name:string}> exact key => lowest-id company
     */
    private $exact = [];

    /**
     * @var array<string, list<int>> exact key => every id, ascending
     */
    private $exact_ids = [];

    /**
     * @var array<string, list<array{id:int,name:string}>> normalized key => companies
     */
    private $by_key = [];

    /**
     * @var array<int, string> id => stored name
     */
    private $names = [];

    /**
     * @param list<array{id:int,name:string}> $companies
     */
    public function __construct(array $companies)
    {
        $seen = [];
        foreach ($companies as $company) {
            $name = $company['name'] ?? null;
            if (!is_string($name) || trim($name) === '') continue;
            $id = (int)$company['id'];
            if (isset($seen[$id])) continue;
            $seen[$id] = true;

            $exact_key = self::exactKey($name);
            $this->exact_ids[$exact_key][] = $id;
            $this->names[$id] = $name;
            if (!isset($this->exact[$exact_key]) || $id < $this->exact[$exact_key]['id']) {
                $this->exact[$exact_key] = ['id' => $id, 'name' => $name];
            }

            $key = self::key($name);
            if ($key === '') continue;
            $this->by_key[$key][] = ['id' => $id, 'name' => $name];
        }
        foreach ($this->exact_ids as $k => $ids) {
            sort($ids);
            $this->exact_ids[$k] = $ids;
        }
    }

    public static function key(string $name): string
    {
        $s = self::nfc($name);
        $s = mb_strtolower($s);
        $s = trim(preg_replace('/[^\p{L}\p{M}\p{N}]+/u', ' ', $s));
        if ($s === '') return '';
        $words = explode(' ', $s);
        while (count($words) > 1 && in_array(end($words), self::LegalSuffixes, true)) {
            array_pop($words);
        }
        return implode(' ', $words);
    }

    // class_exists is defensive: symfony/polyfill-intl-normalizer supplies Normalizer without intl
    private static function nfc(string $name): string
    {
        if (!class_exists(\Normalizer::class)) return $name;
        $s = \Normalizer::normalize($name, \Normalizer::FORM_C);
        return $s === false ? $name : $s;
    }

    private static function exactKey(string $name): string
    {
        $s = self::nfc($name);
        // same trim as CSV cells, so NBSP padding on a stored name can't defeat the exact match
        return mb_strtolower(preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $s));
    }

    public function nameOf(int $id): ?string
    {
        return $this->names[$id] ?? null;
    }

    public function resolve(string $name): array
    {
        $exact_key = self::exactKey($name);
        if ($exact_key !== '' && isset($this->exact[$exact_key])) {
            $hit = $this->exact[$exact_key];
            return ['match' => 'exact', 'id' => $hit['id'], 'name' => $hit['name'], 'ids' => $this->exact_ids[$exact_key]];
        }

        $key = self::key($name);
        if ($key === '' || !isset($this->by_key[$key])) return ['match' => 'none'];

        $candidates = $this->by_key[$key];
        if (count($candidates) === 1) {
            return ['match' => 'normalized', 'id' => $candidates[0]['id'], 'name' => $candidates[0]['name']];
        }
        $names = array_column($candidates, 'name');
        usort($names, 'strcasecmp');
        return ['match' => 'ambiguous', 'candidates' => $names];
    }
}
