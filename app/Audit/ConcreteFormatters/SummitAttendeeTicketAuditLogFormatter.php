<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitAttendeeTicket;
use Illuminate\Support\Facades\Log;

class SummitAttendeeTicketAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitAttendeeTicket) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $owner = $subject->getOwner();
            $owner_name = $owner ? trim(($owner->getFirstName() ?? '') . ' ' . ($owner->getLastName() ?? '')) : 'Unknown Owner';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Attendee Ticket (%s) for '%s' created by user %s", $id, $owner_name, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Attendee Ticket (%s) for '%s' updated: %s by user %s", $id, $owner_name, $details, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Attendee Ticket (%s) for '%s' deleted by user %s", $id, $owner_name, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeTicketAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
