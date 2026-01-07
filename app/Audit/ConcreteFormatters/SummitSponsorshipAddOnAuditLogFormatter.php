<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitSponsorshipAddOn;
use Illuminate\Support\Facades\Log;

class SummitSponsorshipAddOnAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSponsorshipAddOn) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $name = $subject->getName() ?? 'Unknown Add-On';
            $type = $subject->getType() ?? 'Unknown Type';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Sponsorship Add-On (%s) '%s' (%s) created by user %s", $id, $name, $type, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Sponsorship Add-On (%s) '%s' (%s) updated: %s by user %s", $id, $name, $type, $details, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Sponsorship Add-On (%s) '%s' (%s) deleted by user %s", $id, $name, $type, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSponsorshipAddOnAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
