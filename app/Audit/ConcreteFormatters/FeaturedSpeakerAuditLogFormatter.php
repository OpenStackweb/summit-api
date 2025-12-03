<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Speakers\FeaturedSpeaker;
use Illuminate\Support\Facades\Log;

class FeaturedSpeakerAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof FeaturedSpeaker) {
            return null;
        }

        try {
            $speaker = $subject->getSpeaker();
            $speaker_email = $speaker ? ($speaker->getEmail() ?? 'unknown@example.com') : 'unknown@example.com';
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown';
            $speaker_name = trim($speaker_name) ?: $speaker_name;
            $speaker_id = $speaker ? ($speaker->getId() ?? 'unknown') : 'unknown';
            
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            $order = $subject->getOrder();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Speaker '%s' (%s) added as featured speaker for Summit '%s' with display order %d",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $order
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    
                    if (isset($change_set['Order'])) {
                        $old_order = $change_set['Order'][0] ?? 'unknown';
                        $new_order = $change_set['Order'][1] ?? 'unknown';
                        $changed_fields[] = sprintf("display_order %s → %s", $old_order, $new_order);
                    }
                    if (isset($change_set['PresentationSpeakerID'])) {
                        $changed_fields[] = "speaker";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Featured speaker '%s' (%s) updated (%s changed)",
                        $speaker_name,
                        $speaker_id,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Speaker '%s' (%s) removed from featured speakers list of Summit '%s'",
                        $speaker_name,
                        $speaker_id,
                        $summit_name
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("FeaturedSpeakerAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
