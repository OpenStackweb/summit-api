<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitSponsorship;
use Illuminate\Support\Facades\Log;

class SummitSponsorshipAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSponsorship) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $sponsor = $subject->getSponsor();
            $sponsor_name = $sponsor ? ($sponsor->getName() ?? 'Unknown Sponsor') : 'Unknown Sponsor';
            $type = $subject->getType();
            $type_name = $type ? ($type->getName() ?? 'Unknown Type') : 'Unknown Type';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Sponsorship (%s) for '%s' (%s) created by user %s", $id, $sponsor_name, $type_name, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Sponsorship (%s) for '%s' (%s) updated: %s by user %s", $id, $sponsor_name, $type_name, $details, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Sponsorship (%s) for '%s' (%s) deleted by user %s", $id, $sponsor_name, $type_name, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSponsorshipAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
