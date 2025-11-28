<?php namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitTrackChair;
use Illuminate\Support\Facades\Log;

class SummitTrackChairAuditLogFormatter implements IAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitTrackChair) {
            return null;
        }

        try {
            $member = $subject->getMember();
            $member_name = $member ? sprintf("%s %s", $member->getFirstName(), $member->getLastName()) : 'Unknown';
            $member_id = $member ? $member->getId() : 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $categories = [];
                    foreach ($subject->getCategories() as $category) {
                        $categories[] = $category->getTitle();
                    }
                    $tracks_list = !empty($categories) ? implode(', ', $categories) : 'No tracks assigned';
                    return sprintf(
                        "Track Chair '%s' (%d) assigned with tracks: %s",
                        $member_name,
                        $member_id,
                        $tracks_list
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    if (isset($change_set['categories'])) {
                        $old_cats = $change_set['categories'][0] ?? [];
                        $new_cats = $change_set['categories'][1] ?? [];
                        
                        $old_names = is_array($old_cats) 
                            ? array_map(fn($c) => $c->getTitle() ?? 'Unknown', $old_cats)
                            : [];
                        $new_names = is_array($new_cats)
                            ? array_map(fn($c) => $c->getTitle() ?? 'Unknown', $new_cats)
                            : [];
                        
                        $old_str = !empty($old_names) ? implode(', ', $old_names) : 'None';
                        $new_str = !empty($new_names) ? implode(', ', $new_names) : 'None';
                        
                        return sprintf(
                            "Track Chair '%s' tracks changed: [%s] → [%s]",
                            $member_name,
                            $old_str,
                            $new_str
                        );
                    }
                    return sprintf("Track Chair '%s' updated", $member_name);

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Track Chair '%s' (%d) removed from summit",
                        $member_name,
                        $member_id
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitTrackChairAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
