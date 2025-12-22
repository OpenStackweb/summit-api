<?php

namespace App\Audit\ConcreteFormatters\PresentationFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\Presentation;
use Illuminate\Support\Facades\Log;

class PresentationUserSubmissionAuditLogFormatter extends AbstractAuditLogFormatter
{
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
                        "Presentation '%s' (%d) submitted by '%s' to track '%s' (Plan: %s) by user %s",
                        $title,
                        $id,
                        $creator_name,
                        $category_name,
                        $plan_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Presentation '%s' (%d) updated: %s by user %s",
                        $title,
                        $id,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Presentation '%s' (%d) submitted by '%s' to track '%s' was deleted by user %s",
                        $title,
                        $id,
                        $creator_name,
                        $category_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationUserSubmissionAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
