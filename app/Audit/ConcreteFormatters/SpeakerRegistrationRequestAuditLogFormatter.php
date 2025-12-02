<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SpeakerRegistrationRequest;
use Illuminate\Support\Facades\Log;

class SpeakerRegistrationRequestAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SpeakerRegistrationRequest) {
            return null;
        }

        try {
            $email = $subject->getEmail() ?? 'unknown@example.com';
            $speaker = $subject->getSpeaker();
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown';
            $speaker_name = trim($speaker_name) ?: 'Unknown';
            $is_confirmed = $subject->isConfirmed();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Speaker registration request created for email '%s'",
                        $email
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    
                    if (isset($change_set['Email'])) {
                        $old_email = $change_set['Email'][0] ?? 'N/A';
                        $new_email = $change_set['Email'][1] ?? 'N/A';
                        $changed_fields[] = sprintf("email %s → %s", $old_email, $new_email);
                    }
                    if (isset($change_set['IsConfirmed'])) {
                        $old_status = $change_set['IsConfirmed'][0] ? 'confirmed' : 'pending';
                        $new_status = $change_set['IsConfirmed'][1] ? 'confirmed' : 'pending';
                        $changed_fields[] = sprintf("status %s → %s", $old_status, $new_status);
                    }
                    if (isset($change_set['ConfirmationDate'])) {
                        $changed_fields[] = "confirmation_date";
                    }
                    if (isset($change_set['ProposerID'])) {
                        $changed_fields[] = "proposer";
                    }
                    if (isset($change_set['SpeakerID'])) {
                        $changed_fields[] = "speaker";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Speaker registration request for '%s' (%s changed)",
                        $email,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $status = $is_confirmed ? 'confirmed' : 'pending';
                    return sprintf(
                        "Speaker registration request for email '%s' (status: %s) was deleted",
                        $email,
                        $status
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SpeakerRegistrationRequestAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
