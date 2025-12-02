<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;

class SelectionPlanAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    private function formatDate($date): string
    {
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d H:i:s');
        }
        return $date ?? 'N/A';
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SelectionPlan) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown Plan';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? $summit->getName() : 'Unknown Summit';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $submission_dates = $subject->getSubmissionBeginDate() && $subject->getSubmissionEndDate()
                        ? sprintf(
                            "[%s - %s]",
                            $this->formatDate($subject->getSubmissionBeginDate()),
                            $this->formatDate($subject->getSubmissionEndDate())
                        )
                        : 'No dates set';
                    
                    return sprintf(
                        "Selection Plan '%s' (%d) created for Summit '%s' with CFP period: %s",
                        $name,
                        $id,
                        $summit_name,
                        $submission_dates
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    
                    if (isset($change_set['Name'])) {
                        $changed_fields[] = "name";
                    }
                    if (isset($change_set['Enabled'])) {
                        $status = $change_set['Enabled'][1] ? 'enabled' : 'disabled';
                        $changed_fields[] = "status ($status)";
                    }
                    if (isset($change_set['IsHidden'])) {
                        $changed_fields[] = "visibility";
                    }
                    if (isset($change_set['AllowNewPresentations'])) {
                        $changed_fields[] = "allow_new_presentations";
                    }
                    if (isset($change_set['AllowProposedSchedules'])) {
                        $changed_fields[] = "allow_proposed_schedules";
                    }
                    if (isset($change_set['AllowTrackChangeRequests'])) {
                        $changed_fields[] = "allow_track_change_requests";
                    }
                    if (isset($change_set['SubmissionBeginDate']) || isset($change_set['SubmissionEndDate'])) {
                        $old_begin = isset($change_set['SubmissionBeginDate']) ? $this->formatDate($change_set['SubmissionBeginDate'][0]) : 'N/A';
                        $new_begin = isset($change_set['SubmissionBeginDate']) ? $this->formatDate($change_set['SubmissionBeginDate'][1]) : 'N/A';
                        $old_end = isset($change_set['SubmissionEndDate']) ? $this->formatDate($change_set['SubmissionEndDate'][0]) : 'N/A';
                        $new_end = isset($change_set['SubmissionEndDate']) ? $this->formatDate($change_set['SubmissionEndDate'][1]) : 'N/A';
                        $changed_fields[] = sprintf("CFP period [%s - %s] → [%s - %s]", $old_begin, $old_end, $new_begin, $new_end);
                    }
                    if (isset($change_set['VotingBeginDate']) || isset($change_set['VotingEndDate'])) {
                        $changed_fields[] = "voting period";
                    }
                    if (isset($change_set['SelectionBeginDate']) || isset($change_set['SelectionEndDate'])) {
                        $changed_fields[] = "selection period";
                    }
                    if (isset($change_set['MaxSubmissionAllowedPerUser'])) {
                        $old_val = $change_set['MaxSubmissionAllowedPerUser'][0] ?? 0;
                        $new_val = $change_set['MaxSubmissionAllowedPerUser'][1] ?? 0;
                        $changed_fields[] = sprintf("max_submissions (%d → %d)", $old_val, $new_val);
                    }
                    if (isset($change_set['SubmissionPeriodDisclaimer'])) {
                        $changed_fields[] = "disclaimer";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Selection Plan '%s' (%d) for Summit '%s' updated (%s changed)",
                        $name,
                        $id,
                        $summit_name,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Selection Plan '%s' (%d) for Summit '%s' was deleted",
                        $name,
                        $id,
                        $summit_name
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SelectionPlanAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
