<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitAttendee;
use Illuminate\Support\Facades\Log;

class SummitAttendeeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitAttendee) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $name = trim(($subject->getFirstName() ?? '') . ' ' . ($subject->getLastName() ?? '')) ?: 'Unknown Attendee';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Attendee (%s) '%s' created by user %s", $id, $name, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Attendee (%s) '%s' updated: %s by user %s", $id, $name, $details, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Attendee (%s) '%s' deleted by user %s", $id, $name, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
