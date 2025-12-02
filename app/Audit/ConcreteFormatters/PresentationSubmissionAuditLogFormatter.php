<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\Presentation;
use Illuminate\Support\Facades\Log;

class PresentationSubmissionAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof Presentation) {
            return null;
        }

        try {
            $title = $subject->getTitle() ?? 'Unknown Presentation';
            $id = $subject->getId() ?? 'unknown';
            $creator = $subject->getCreator();
            $creator_name = $creator ? sprintf("%s %s", $creator->getFirstName() ?? '', $creator->getLastName() ?? '') : 'Unknown';
            $creator_name = trim($creator_name) ?: 'Unknown';
            $category = $subject->getCategory();
            $category_name = $category ? $category->getTitle() : 'Unassigned Track';
            $selection_plan = $subject->getSelectionPlan();
            $plan_name = $selection_plan ? $selection_plan->getName() : 'Unknown Plan';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Presentation '%s' (%d) submitted by '%s' to track '%s' (Plan: %s)",
                        $title,
                        $id,
                        $creator_name,
                        $category_name,
                        $plan_name
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    $old_status = null;
                    $new_status = null;

                    if (isset($change_set['Title'])) {
                        $changed_fields[] = "title";
                    }
                    if (isset($change_set['Abstract'])) {
                        $changed_fields[] = "abstract";
                    }
                    if (isset($change_set['ProblemAddressed'])) {
                        $changed_fields[] = "problem_addressed";
                    }
                    if (isset($change_set['AttendeesExpectedLearnt'])) {
                        $changed_fields[] = "attendees_expected_learnt";
                    }
                    
                    if (isset($change_set['Status'])) {
                        $changed_fields[] = "status";
                        $old_status = $change_set['Status'][0] ?? null;
                        $new_status = $change_set['Status'][1] ?? null;
                    }
                    if (isset($change_set['CategoryID']) || isset($change_set['category'])) {
                        $changed_fields[] = "track";
                    }
                    if (isset($change_set['Published'])) {
                        $changed_fields[] = "published";
                    }
                    if (isset($change_set['SelectionPlanID'])) {
                        $changed_fields[] = "selection_plan";
                    }

                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    
                    if ($old_status && $new_status) {
                        return sprintf(
                            "Presentation '%s' (%d) status changed: %s → %s (%s changed)",
                            $title,
                            $id,
                            strtoupper($old_status),
                            strtoupper($new_status),
                            $fields_str
                        );
                    }

                    return sprintf(
                        "Presentation '%s' (%d) updated (%s changed)",
                        $title,
                        $id,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Presentation '%s' (%d) submitted by '%s' to track '%s' was deleted",
                        $title,
                        $id,
                        $creator_name,
                        $category_name
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationSubmissionAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
