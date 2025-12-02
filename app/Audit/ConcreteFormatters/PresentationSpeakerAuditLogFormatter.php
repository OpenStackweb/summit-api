<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\PresentationSpeaker;
use Illuminate\Support\Facades\Log;

class PresentationSpeakerAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationSpeaker) {
            return null;
        }

        try {
            $full_name = sprintf("%s %s", $subject->getFirstName() ?? 'Unknown', $subject->getLastName() ?? 'Unknown');
            $email = $subject->getEmail() ?? 'unknown@example.com';
            $speaker_id = $subject->getId() ?? 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $bio = $subject->getBio() ? sprintf(" - Bio: %s", mb_substr($subject->getBio(), 0, 50)) : '';
                    return sprintf(
                        "Speaker '%s' (%s) created with email '%s'%s",
                        $full_name,
                        $speaker_id,
                        $email,
                        $bio
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    if (isset($change_set['FirstName']) || isset($change_set['LastName'])) {
                        $changed_fields[] = "name";
                    }
                    if (isset($change_set['Email'])) {
                        $changed_fields[] = "email";
                    }
                    if (isset($change_set['Title'])) {
                        $changed_fields[] = "title";
                    }
                   
                    if (isset($change_set['Country'])) {
                        $changed_fields[] = "country";
                    }
                    if (isset($change_set['AvailableForBureau'])) {
                        $changed_fields[] = "available_for_bureau";
                    }
                    if (isset($change_set['FundedTravel'])) {
                        $changed_fields[] = "funded_travel";
                    }
                    if (isset($change_set['WillingToTravel'])) {
                        $changed_fields[] = "willing_to_travel";
                    }
                    if (isset($change_set['WillingToPresentVideo'])) {
                        $changed_fields[] = "willing_to_present_video";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Speaker '%s' (%s) updated (%s changed)",
                        $full_name,
                        $speaker_id,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Speaker '%s' (%s) with email '%s' was deleted",
                        $full_name,
                        $speaker_id,
                        $email
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationSpeakerAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
