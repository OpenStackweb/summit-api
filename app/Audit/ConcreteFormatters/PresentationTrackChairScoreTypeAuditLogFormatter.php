<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use Illuminate\Support\Facades\Log;

class PresentationTrackChairScoreTypeAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationTrackChairScoreType) {
            return null;
        }

        try {
            $label = $subject->getLabel() ?? 'Unknown';
            $score = $subject->getScore() ?? 'unknown';
            $rating_type = $subject->getRatingType();
            $rating_type_name = $rating_type ? $rating_type->getName() : 'Unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Score Type '%s' (value: %s) added to Rating Type '%s'",
                        $label,
                        $score,
                        $rating_type_name
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    if (isset($change_set['label'])) {
                        $changed_fields[] = "label";
                    }
                    if (isset($change_set['score'])) {
                        $changed_fields[] = "score";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Score Type '%s' updated (%s changed)",
                        $label,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Score Type '%s' removed from Rating Type '%s'",
                        $label,
                        $rating_type_name
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationTrackChairScoreTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
