<?php namespace App\Repositories\Summit;

use App\Http\Utils\Filters\DoctrineInFilterMapping;
use App\Http\Utils\Filters\DoctrineNotInFilterMapping;
use App\Models\Foundation\Summit\Events\RSVP\Repositories\IRSVPInvitationRepository;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use models\summit\SummitEvent;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;

final class DoctrineRSVPInvitationRepository
    extends SilverStripeDoctrineRepository
implements IRSVPInvitationRepository
{

    protected function getBaseEntity()
    {
       return RSVPInvitation::class;
    }


    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null){
        $query = $query->join('e.summit_event', 'se');
        $query = $query->join('e.invitee', 'a');
        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'id' => new DoctrineInFilterMapping('e.id'),
            'not_id' => new DoctrineNotInFilterMapping('e.id'),
            'attendee_email' => 'a.email:json_string',
            'attendee_first_name' => Filter::buildLowerCaseStringField('a.first_name'),
            'attendee_last_name' => Filter::buildLowerCaseStringField('a.surname'),
            'attendee_full_name' => Filter::buildConcatStringFields(['a.first_name', 'a.surname']),
            'is_accepted' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf("e.status = '%s'", RSVPInvitation::Status_Accepted)
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        sprintf("e.status <> '%s'", RSVPInvitation::Status_Accepted)
                    ),
                ]
            ),
            'is_sent' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.hash is not null"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.hash is null"
                    ),
                ]
            ),
            'summit_event_id' => "se.id",
            'status' => "e.status :operator :value",
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id'   => 'e.id',
            'attendee_email' => 'a.email',
            'attendee_first_name' => 'a.first_name',
            'attendee_last_name' => 'a.surname',
            'attendee_full_name'=> Filter::buildConcatStringFields(['a.first_name', 'a.surname']),
            'status' => 'e.status',
        ];
    }

    /**
     * @param SummitEvent $summit_event
     * @return array|int[]
     */
    public function getAllIdsNonAcceptedPerSummitEvent(SummitEvent $summit_event): array
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e.id")
            ->from($this->getBaseEntity(), "e")
            ->join("e.summit_event","se")
            ->where('e.accepted_date is null')
            ->andWhere('se.id = :event_id')->setParameter("event_id", $summit_event->getId());
        return $query->getQuery()->getResult();
    }

    /**
     * @param string $hash
     * @param SummitEvent $summit_event
     * @return RSVPInvitation|null
     */
    public function getByHashAndSummitEvent(string $hash, SummitEvent $summit_event): ?RSVPInvitation
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.summit_event","se")
            ->where('e.hash = :hash')
            ->andWhere('se.id = :event_id')
            ->setParameter("event_id", $summit_event->getId())
            ->setParameter('hash', trim($hash));

        return $query->getQuery()->getOneOrNullResult();
    }

    public function getByHashExclusiveLock(string $hash): ?RSVPInvitation
    {
        return $this->findOneBy(['hash'=> trim($hash)]);
    }
}