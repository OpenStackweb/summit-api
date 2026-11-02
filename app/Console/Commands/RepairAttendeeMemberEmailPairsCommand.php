<?php namespace App\Console\Commands;
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
use App\Jobs\UpdateAttendeeInfo;
use App\Jobs\Utils\JobDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class RepairAttendeeMemberEmailPairsCommand
 *
 * A pair is an attendee linked to a member whose email differs from the member's current one,
 * plus another attendee of the same summit with no member that already has the member's email.
 * Only UpdateAttendeeInfo merges a pair, and only when the member's email is verified.
 *
 * Without --dispatch it only reads: run it before and after the repair.
 * @package App\Console\Commands
 */
class RepairAttendeeMemberEmailPairsCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'summit:repair-attendee-member-email-pairs';

    /**
     * @var string
     */
    protected $signature = 'summit:repair-attendee-member-email-pairs {--dispatch : enqueue UpdateAttendeeInfo for each member of a pair with a verified email} {--limit= : dispatch at most this many members} {--after-member-id= : only dispatch members with an id greater than this one, to resume after the last dispatched member}';

    /**
     * @var string
     */
    protected $description = 'Lists the attendee/member email pairs and dispatches UpdateAttendeeInfo for the ones with a verified email';

    /**
     * @return array
     */
    private function getPairs(): array
    {
        return DB::connection('model')->select(<<<'SQL'
SELECT
    a.SummitID                  AS summit_id,
    m.ID                        AS member_id,
    m.EmailVerified             AS email_verified,
    a.ID                        AS attendee_id,
    b.ID                        AS memberless_attendee_id
FROM SummitAttendee a
JOIN Member m ON m.ID = a.MemberID
JOIN SummitAttendee b
    ON b.SummitID = a.SummitID AND b.MemberID IS NULL AND b.Email = m.Email
WHERE NOT (a.Email <=> m.Email)
ORDER BY a.SummitID, m.EmailVerified DESC, m.ID
SQL
        );
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $pairs = $this->getPairs();

        $groups = [];
        foreach ($pairs as $pair) {
            $key = sprintf("%s|%s", $pair->summit_id, $pair->email_verified);
            $groups[$key] = ($groups[$key] ?? 0) + 1;
        }
        ksort($groups);

        $this->info(sprintf("%s pairs", count($pairs)));
        $rows = [];
        foreach ($groups as $key => $count) {
            [$summit_id, $verified] = explode('|', $key);
            $rows[] = [$summit_id, $verified ? 'yes' : 'no', $count];
        }
        $this->table(['summit_id', 'member email verified', 'pairs'], $rows);

        // ids only, no emails: enough to inspect the attendees and to follow up on the unverified ones
        $detail = [];
        foreach ($pairs as $pair) {
            $detail[] = [
                $pair->summit_id,
                $pair->member_id,
                $pair->email_verified ? 'yes' : 'no',
                $pair->attendee_id,
                $pair->memberless_attendee_id,
            ];
        }
        $this->table(['summit_id', 'member_id', 'member email verified', 'attendee_id', 'member-less attendee_id'], $detail);

        if (!$this->option('dispatch')) {
            $this->line('Dry run, nothing dispatched. Use --dispatch to enqueue UpdateAttendeeInfo for the verified ones.');
            return 0;
        }

        // one job per member: it updates the member's attendees in every summit
        $member_ids = [];
        foreach ($pairs as $pair) {
            if (!$pair->email_verified) continue;
            $member_ids[(int)$pair->member_id] = true;
        }
        $member_ids = array_keys($member_ids);
        // stable order, so --after-member-id can resume past a member whose job keeps failing
        sort($member_ids);

        $after_member_id = $this->option('after-member-id');
        if (!is_null($after_member_id) && $after_member_id !== '') {
            $after_member_id = intval($after_member_id);
            $member_ids = array_values(array_filter($member_ids, fn(int $id) => $id > $after_member_id));
        }

        $limit = $this->option('limit');
        if (!is_null($limit) && $limit !== '')
            $member_ids = array_slice($member_ids, 0, max(0, intval($limit)));

        foreach ($member_ids as $member_id) {
            Log::info(sprintf("RepairAttendeeMemberEmailPairsCommand dispatching UpdateAttendeeInfo member id %s", $member_id));
            JobDispatcher::withDbFallback(
                job: new UpdateAttendeeInfo($member_id),
                logContext: ['member_id' => $member_id]
            );
        }

        $this->info(sprintf("dispatched UpdateAttendeeInfo for %s members", count($member_ids)));
        if (count($member_ids))
            $this->line(sprintf("last member id dispatched: %s (resume with --after-member-id=%s)", end($member_ids), end($member_ids)));
        return 0;
    }
}
