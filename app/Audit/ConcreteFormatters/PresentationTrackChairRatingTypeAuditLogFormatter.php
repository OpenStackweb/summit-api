<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use Illuminate\Support\Facades\Log;

class PresentationTrackChairRatingTypeAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationTrackChairRatingType) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown';
            $selection_plan = $subject->getSelectionPlan();
            $plan_name = $selection_plan ? $selection_plan->getName() : 'Unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Track Chair Rating Type '%s' created for Selection Plan '%s'",
                        $name,
                        $plan_name
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    if (isset($change_set['name'])) {
                        $changed_fields[] = "name";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Track Chair Rating Type '%s' updated (%s changed)",
                        $name,
                        $fields_str
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Track Chair Rating Type '%s' deleted from Selection Plan '%s'",
                        $name,
                        $plan_name
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationTrackChairRatingTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
