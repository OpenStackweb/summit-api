<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\PresentationAttendeeVote;
use Illuminate\Support\Facades\Log;

class PresentationAttendeeVoteAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationAttendeeVote) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $presentation = $subject->getPresentation();
            $title = $presentation ? ($presentation->getTitle() ?? 'Unknown Presentation') : 'Unknown Presentation';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Presentation Attendee Vote (%s) for '%s' created by user %s", $id, $title, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Presentation Attendee Vote (%s) for '%s' updated: %s by user %s", $id, $title, $details, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Presentation Attendee Vote (%s) for '%s' deleted by user %s", $id, $title, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationAttendeeVoteAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
