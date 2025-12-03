<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use Illuminate\Support\Facades\Log;

class SpeakerAssistanceAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationSpeakerSummitAssistanceConfirmationRequest) {
            return null;
        }

        try {
            $speaker = $subject->getSpeaker();
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown';
            $speaker_email = $speaker ? ($speaker->getEmail() ?? 'unknown@example.com') : 'unknown@example.com';
            $speaker_name = trim($speaker_name) ?: $speaker_email;
            $speaker_id = $speaker ? ($speaker->getId() ?? 'unknown') : 'unknown';
            
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            $is_confirmed = $subject->isConfirmed();
            $is_registered = $subject->isRegistered();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $status = $is_confirmed ? 'confirmed' : 'pending';
                    $registration_status = $is_registered ? 'registered' : 'unregistered';
                    return sprintf(
                        "Speaker assistance created for '%s' (%s) on Summit '%s' [confirmation: %s, registration: %s]",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $status,
                        $registration_status
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    
                    if (isset($change_set['IsConfirmed'])) {
                        $old_status = $change_set['IsConfirmed'][0] ? 'confirmed' : 'pending';
                        $new_status = $change_set['IsConfirmed'][1] ? 'confirmed' : 'pending';
                        $changed_fields[] = sprintf("confirmation %s → %s", $old_status, $new_status);
                    }
                    if (isset($change_set['CheckedIn'])) {
                        $old_status = $change_set['CheckedIn'][0] ? 'checked_in' : 'not_checked_in';
                        $new_status = $change_set['CheckedIn'][1] ? 'checked_in' : 'not_checked_in';
                        $changed_fields[] = sprintf("check_in %s → %s", $old_status, $new_status);
                    }
                    if (isset($change_set['RegisteredForSummit'])) {
                        $old_status = $change_set['RegisteredForSummit'][0] ? 'registered' : 'unregistered';
                        $new_status = $change_set['RegisteredForSummit'][1] ? 'registered' : 'unregistered';
                        $changed_fields[] = sprintf("registration %s → %s", $old_status, $new_status);
                    }
                    if (isset($change_set['OnSitePhoneNumber'])) {
                        $old_phone = $change_set['OnSitePhoneNumber'][0] ?? 'N/A';
                        $new_phone = $change_set['OnSitePhoneNumber'][1] ?? 'N/A';
                        $changed_fields[] = "on_site_phone";
                    }
                    if (isset($change_set['ConfirmationDate'])) {
                        $changed_fields[] = "confirmation_date";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Speaker assistance for '%s' (%s) on Summit '%s' updated (%s changed)",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $status = $is_confirmed ? 'confirmed' : 'pending';
                    $registration_status = $is_registered ? 'registered' : 'unregistered';
                    return sprintf(
                        "Speaker assistance for '%s' (%s) on Summit '%s' [confirmation: %s, registration: %s] was deleted",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $status,
                        $registration_status
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SpeakerAssistanceAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
