<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitAttendee_Tags as SummitAttendeeTag;
use Illuminate\Support\Facades\Log;

class SummitAttendeeTagAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        // Some relations are stored in pivot-like tables; be defensive
        try {
            $id = null;
            if (is_object($subject) && method_exists($subject, 'getId')) {
                $id = $subject->getId();
            }
            $id = $id ?? 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Attendee Tag (%s) created by user %s", $id, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Attendee Tag (%s) deleted by user %s", $id, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeTagAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
