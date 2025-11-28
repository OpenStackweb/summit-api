<?php namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use Illuminate\Support\Facades\Log;

class PresentationTrackChairScoreAuditLogFormatter implements IChildEntityAuditLogFormatter
{
    public function format($subject, string $child_entity_action_type, ?string $additional_info = ""): ?string
    {
        if (!$subject instanceof PresentationTrackChairScore) {
            return null;
        }

        try {
            $score_type = $subject->getScoreType();
            $score_label = $score_type ? $score_type->getLabel() : 'Unknown Score';
            
            $presentation = $subject->getPresentation();
            $presentation_title = $presentation ? $presentation->getTitle() : 'Unknown Presentation';
            
            $created_by = $subject->getCreatedBy();
            $chair_name = $created_by 
                ? sprintf("%s %s", $created_by->getFirstName(), $created_by->getLastName())
                : 'Unknown Chair';

            switch ($child_entity_action_type) {
                case self::CHILD_ENTITY_CREATION:
                    return sprintf(
                        "Track Chair '%s' scored '%s' on presentation '%s'",
                        $chair_name,
                        $score_label,
                        $presentation_title
                    );
                case self::CHILD_ENTITY_DELETION:
                    return sprintf(
                        "Score removed for Track Chair '%s' from presentation '%s'",
                        $chair_name,
                        $presentation_title
                    );
                case self::CHILD_ENTITY_UPDATE:
                    return sprintf(
                        "Track Chair '%s' score updated to '%s' on presentation '%s'",
                        $chair_name,
                        $score_label,
                        $presentation_title
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationTrackChairScoreAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
