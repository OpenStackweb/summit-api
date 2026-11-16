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

use services\model\ISummitSponsorService;

/**
 * Class SponsorImportPlanner
 * Turns parsed CSV rows into planned rows (what the import would do), without touching the database.
 * @package App\Services\Model\Imp\SponsorImport
 */
final class SponsorImportPlanner
{
    /**
     * @var CompanyNameMatcher
     */
    private $matcher;

    /**
     * @var array<string, array{id:int,name:string}> lowercase tier name => tier
     */
    private $tiers;

    /**
     * @var array<int, true> company id => true
     */
    private $sponsor_company_ids;

    /**
     * @param CompanyNameMatcher $matcher
     * @param array<string, array{id:int,name:string}> $tiers
     * @param list<int> $sponsor_company_ids
     */
    public function __construct(CompanyNameMatcher $matcher, array $tiers, array $sponsor_company_ids)
    {
        $this->matcher = $matcher;
        $this->tiers = $tiers;
        $this->sponsor_company_ids = array_fill_keys(array_map('intval', $sponsor_company_ids), true);
    }

    /**
     * @param list<array{row:int,company:string,tier:string}> $rows
     * @return list<array>
     */
    public function plan(array $rows): array
    {
        $planned = [];
        $seen = []; // identity => row number, non-error rows only
        foreach ($rows as $input) {
            $row = $this->newRow($input);
            // NBSP too: cells pasted from web pages or Excel carry it
            $company_name = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $input['company']);

            if ($company_name === '') {
                $planned[] = $this->fail($row, 'Company is required.');
                continue;
            }

            $tier_names = [];
            foreach (explode(ISummitSponsorService::ImportTierSeparator, $input['tier']) as $segment) {
                $segment = trim($segment);
                if ($segment !== '') $tier_names[] = $segment;
            }
            if (count($tier_names) === 0) {
                $planned[] = $this->fail($row, 'Tier is required.');
                continue;
            }

            $tier_ids = [];
            $tier_labels = [];
            $unknown = null;
            foreach ($tier_names as $name) {
                $tier = $this->tiers[mb_strtolower($name)] ?? null;
                if ($tier === null) {
                    $unknown = $name;
                    break;
                }
                if (in_array($tier['id'], $tier_ids, true)) continue;
                $tier_ids[] = $tier['id'];
                $tier_labels[] = $tier['name'];
            }
            if ($unknown !== null) {
                $available = implode(', ', array_column($this->tiers, 'name'));
                $planned[] = $this->fail($row, sprintf('Unknown tier "%s". This show\'s tiers: %s.', $unknown, $available));
                continue;
            }
            $row['tiers'] = $tier_labels;
            $row['tier_ids'] = $tier_ids;

            $match = $this->matcher->resolve($company_name);
            if ($match['match'] === 'ambiguous') {
                $row['candidates'] = $match['candidates'];
                $planned[] = $this->fail($row, sprintf(
                    'Several companies match "%s". Use the exact name of one of: %s',
                    $company_name, implode(', ', $match['candidates'])));
                continue;
            }

            if ($match['match'] === 'none') {
                $row['company'] = ['id' => null, 'name' => $company_name, 'match' => 'new'];
                $key = CompanyNameMatcher::key($company_name);
                $identity = $key !== '' ? 'key:' . $key : 'name:' . mb_strtolower($company_name);
                $is_sponsor = false;
            } else {
                $id = $match['id'];
                if ($match['match'] === 'exact') {
                    foreach ($match['ids'] as $candidate_id) {
                        if (isset($this->sponsor_company_ids[$candidate_id])) {
                            $id = $candidate_id;
                            break;
                        }
                    }
                }
                $row['company'] = ['id' => $id, 'name' => $match['name'], 'match' => $match['match']];
                $identity = 'id:' . $id;
                $is_sponsor = isset($this->sponsor_company_ids[$id]);
            }

            if ($is_sponsor) {
                $row['action'] = 'skip_existing_sponsor';
            } elseif (isset($seen[$identity])) {
                $row['action'] = 'skip_duplicate';
                $row['duplicate_of_row'] = $seen[$identity];
            } else {
                $row['action'] = 'create';
            }
            $seen[$identity] = $seen[$identity] ?? $row['row'];
            $planned[] = $row;
        }
        return $planned;
    }

    /**
     * @param list<array> $rows
     * @param bool $dry_run
     * @return array{dry_run:bool,summary:array<string,int>}
     */
    public static function summarize(array $rows, bool $dry_run): array
    {
        $summary = [
            'total' => count($rows),
            'sponsors_created' => 0,
            'companies_created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'not_attempted' => 0,
        ];
        foreach ($rows as $row) {
            $action = $row['action'];
            if ($action === 'create') $summary['sponsors_created']++;
            elseif ($action === 'skip_existing_sponsor' || $action === 'skip_duplicate') $summary['skipped']++;
            elseif ($action === 'error') $summary['errors']++;
            elseif ($action === 'not_attempted') $summary['not_attempted']++;

            // a company created before its sponsor failed still counts
            if (($row['company']['match'] ?? null) === 'new' &&
                ($action === 'create' || ($row['company']['id'] ?? null) !== null)) {
                $summary['companies_created']++;
            }
        }
        return ['dry_run' => $dry_run, 'summary' => $summary];
    }

    /**
     * Marks row $index as an error with $message, and turns any later skip_duplicate of it into not_attempted.
     * @param list<array> $rows
     * @param int $index
     * @param string $message
     * @return list<array>
     */
    public static function markFailed(array $rows, int $index, string $message): array
    {
        $rows[$index]['action'] = 'error';
        $rows[$index]['error'] = $message;
        $failed_row = $rows[$index]['row'];
        $count = count($rows);
        for ($i = $index + 1; $i < $count; $i++) {
            if ($rows[$i]['action'] === 'skip_duplicate' && ($rows[$i]['duplicate_of_row'] ?? null) === $failed_row) {
                $rows[$i]['action'] = 'not_attempted';
            }
        }
        return $rows;
    }

    /**
     * Marks row $index as an error with $message and turns every later planned create into not_attempted,
     * along with any later skip_duplicate whose original row was stopped or not attempted.
     * @param list<array> $rows
     * @param int $index
     * @param string $message
     * @return list<array>
     */
    public static function markStopped(array $rows, int $index, string $message): array
    {
        $rows[$index]['action'] = 'error';
        $rows[$index]['error'] = $message;
        $stopped = [$rows[$index]['row'] => true]; // row numbers with nothing imported
        $count = count($rows);
        for ($i = $index + 1; $i < $count; $i++) {
            $action_i = $rows[$i]['action'];
            if ($action_i === 'create' ||
                ($action_i === 'skip_duplicate' && isset($stopped[$rows[$i]['duplicate_of_row'] ?? null]))) {
                $rows[$i]['action'] = 'not_attempted';
                $stopped[$rows[$i]['row']] = true;
            }
        }
        return $rows;
    }

    private function newRow(array $input): array
    {
        return [
            'row' => $input['row'],
            'company_input' => $input['company'],
            'tier_input' => $input['tier'],
            'action' => 'create',
            'company' => null,
            'tiers' => [],
            'sponsor_id' => null,
            'duplicate_of_row' => null,
            'candidates' => [],
            'error' => null,
            'tier_ids' => [],
        ];
    }

    private function fail(array $row, string $message): array
    {
        $row['action'] = 'error';
        $row['error'] = $message;
        return $row;
    }
}
