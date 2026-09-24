<?php namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\Log;
use models\summit\Presentation;
use models\summit\SummitTrackChair;

class PresentationTrackChairScoreAuditLogFormatter
    implements IChildEntityAuditLogFormatter, IChildEntityCollectionAuditLogFormatter
{
    /**
     * Changing a score is modeled as remove old + add new in the same transaction, so a
     * delete and an insert for the same presentation and rating type are rendered as one
     * "changed" entry instead of two joined actions.
     * @param PersistentCollection $collection
     * @return string|null
     */
    public function formatCollection(PersistentCollection $collection): ?string
    {
        try {
            $owner = $collection->getOwner();

            $removed = [];
            foreach ($collection->getDeleteDiff() as $score) {
                if (!$score instanceof PresentationTrackChairScore) continue;
                $removed[$this->getReplacementKey($score, $owner)] = $score;
            }

            $lines = [];
            foreach ($collection->getInsertDiff() as $score) {
                if (!$score instanceof PresentationTrackChairScore) continue;
                $key = $this->getReplacementKey($score, $owner);
                $old = $removed[$key] ?? null;
                unset($removed[$key]);

                $lines[] = is_null($old)
                    ? sprintf(
                        "Track Chair '%s' scored '%s' on presentation '%s'",
                        $this->getChairName($score, $owner),
                        $score->getType()->getName(),
                        $this->getPresentationTitle($score, $owner)
                    )
                    : sprintf(
                        "Track Chair '%s' changed score from '%s' to '%s' on presentation '%s'",
                        $this->getChairName($score, $owner),
                        $old->getType()->getName(),
                        $score->getType()->getName(),
                        $this->getPresentationTitle($score, $owner)
                    );
            }

            foreach ($removed as $score) {
                $lines[] = sprintf(
                    "Track Chair '%s' removed score '%s' from presentation '%s'",
                    $this->getChairName($score, $owner),
                    $score->getType()->getName(),
                    $this->getPresentationTitle($score, $owner)
                );
            }

            return empty($lines) ? null : implode(' | ', $lines);
        } catch (\Exception $ex) {
            Log::warning("PresentationTrackChairScoreAuditLogFormatter::formatCollection error: " . $ex->getMessage());
        }

        return null;
    }

    private function getReplacementKey(PresentationTrackChairScore $score, $owner): string
    {
        $presentation = $owner instanceof Presentation ? $owner : ($score->hasPresentation() ? $score->getPresentation() : null);
        return sprintf("%s:%s", $presentation?->getId() ?? 0, $score->getType()->getType()->getId());
    }

    /**
     * removeScore() / removeTrackChairScore() null the back reference on the removed score,
     * so the side that owns the collection is taken from the collection owner.
     */
    private function getChairName(PresentationTrackChairScore $score, $owner): string
    {
        $chair = $owner instanceof SummitTrackChair ? $owner : ($score->hasReviewer() ? $score->getReviewer() : null);
        return is_null($chair) ? 'Unknown Chair' : ($chair->getMember()->getFullName() ?? 'Unknown Chair');
    }

    private function getPresentationTitle(PresentationTrackChairScore $score, $owner): string
    {
        $presentation = $owner instanceof Presentation ? $owner : ($score->hasPresentation() ? $score->getPresentation() : null);
        return is_null($presentation) ? 'Unknown Presentation' : $presentation->getTitle();
    }

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
