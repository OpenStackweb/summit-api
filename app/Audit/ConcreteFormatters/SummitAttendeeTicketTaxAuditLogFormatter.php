<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitAttendeeTicketTax;
use Illuminate\Support\Facades\Log;

class SummitAttendeeTicketTaxAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitAttendeeTicketTax) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $tax = $subject->getTax();
            $tax_name = $tax ? ($tax->getName() ?? 'Unknown Tax') : 'Unknown Tax';
            $ticket = $subject->getTicket();
            $ticket_id = $ticket ? $ticket->getId() : 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Attendee Ticket Tax (%s) '%s' for ticket %s created by user %s", $id, $tax_name, $ticket_id, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Attendee Ticket Tax (%s) '%s' for ticket %s updated: %s by user %s", $id, $tax_name, $ticket_id, $details, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Attendee Ticket Tax (%s) '%s' for ticket %s deleted by user %s", $id, $tax_name, $ticket_id, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeTicketTaxAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
