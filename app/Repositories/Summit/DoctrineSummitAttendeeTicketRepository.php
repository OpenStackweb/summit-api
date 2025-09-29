<?php namespace App\Repositories\Summit;
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\Http\Utils\Filters\DoctrineInFilterMapping;
use App\Http\Utils\Filters\DoctrineNotInFilterMapping;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRefundRequestConstants;
use models\summit\Summit;
use models\summit\SummitAccessLevelType;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\utils\IEntity;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSummitAttendeeTicketRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitAttendeeTicketRepository
    extends SilverStripeDoctrineRepository
    implements ISummitAttendeeTicketRepository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->fetchJoinCollection = false;
    }


    /** @var array<string, array{0:string,1:'join'|'leftJoin',2:array<int,string>}> */
    private array $joinCatalog = [
        'o'      => ['e.order',               'join',     []],
        's'      => ['o.summit',              'join',     ['o']],
        'ord_m'  => ['o.owner',               'leftJoin', ['o']],
        'a'      => ['e.owner',               'leftJoin', []],
        'a_c'    => ['a.company',             'leftJoin', ['a']],
        'm'      => ['a.member',              'leftJoin', ['a']],
        'am'     => ['a.manager',             'leftJoin', ['a']],
        'm2'     => ['am.member',             'leftJoin', ['am']],
        'b'      => ['e.badge',               'leftJoin', []],
        'bt'     => ['b.type',                'leftJoin', ['b']],
        'al'     => ['bt.access_levels',      'leftJoin', ['bt']],
        'bf'     => ['b.features',            'leftJoin', ['b']],
        'bt_bf'  => ['bt.badge_features',     'leftJoin', ['bt']],
        'prt'    => ['b.prints',              'leftJoin', ['b']],
        'rr'     => ['e.refund_requests',     'leftJoin', []],
        'ta'     => ['e.applied_taxes',       'leftJoin', []],
        'tt'     => ['e.ticket_type',         'join',     []],
        'pc'     => ['e.promo_code',          'leftJoin', []],
        'pct'    => ['pc.tags',               'leftJoin', ['pc']],
        'avt'    => ['bt.allowed_view_types', 'join',     ['bt']],
    ];

    private function ensureJoin(QueryBuilder $qb, string $alias): void
    {
        if (\in_array($alias, $qb->getAllAliases(), true)) return;

        [$path, $type, $deps] = $this->joinCatalog[$alias] ?? [null, null, []];
        foreach ($deps as $dep) $this->ensureJoin($qb, $dep);

        if ($type === 'join') $qb->join($path, $alias);
        else                  $qb->leftJoin($path, $alias);
    }

    /**
     * choose alias needed
     * @return string[]
     */
    private function requiredAliases(?Filter $filter, ?Order $order): array
    {
        $need = []; // owner always

        $has = fn(string $f) => $filter?->hasFilter($f) ?? false;
        $ord = fn(string $f) => $order?->hasOrder($f) ?? false;
        $val = fn(string $f) => $filter?->getValue($f)[0] ?? null;

        // --- Filters ---
        if ($has('order_number') || $has('order_id') || $has('order_owner_id') || $has('bought_date') || $has('summit_id')) {
            $need['o'] = true;
            if($has('order_owner_id')){
                $this->joinCatalog['ord_m'][1] = 'join';
                $need['ord_m'] = true;
            }
        }
        if ($has('summit_id')) $need['s'] = true;

        if ($has('owner_first_name') || $has('owner_last_name') || $has('owner_name') || $has('owner_id') || $has('member_id')) {
            $need['a'] = true;
            if($has('owner_first_name') || $has('owner_last_name') || $has('owner_name') || $has('member_id')) $need['m'] = true;
        }

        if ($has('owner_email')) {
            $need['a'] = $need['m'] = $need['am'] = $need['m2'] = true;
        }

        if ($has('owner_company') || $has('has_owner_company')) { $need['a'] = $need['a_c'] = true; }

        if ($has('has_owner')) {
            if ((string)$val('has_owner') === '1') $this->joinCatalog['a'][1] = 'join';
            $need['a'] = true;
        }

        if ($has('owner_status')) {
            $this->joinCatalog['a'][1] = 'join';
            $need['a'] = true;
        }

        if ($has('has_order_owner')) {
            if ((string)$val('has_order_owner') === '1') $this->joinCatalog['ord_m'][1] = 'join';
            $need['o'] = $need['ord_m'] = true;
        }

        if ($has('assigned_to')) { $need['a'] = true; $need['m'] = true; } // usa m.id y a.email

        if ($has('promo_code') || $has('promo_code_id') || $has('promo_code_description')) {
            $need['pc'] = true;
        }
        if ($has('promo_code_tag') || $has('promo_code_tag_id')) {
            $need['pc'] = $need['pct'] = true;
        }

        if ($has('ticket_type_id') || $ord('ticket_type')) $need['tt'] = true;

        if ($has('has_badge') || $ord('badge_type') || $ord('badge_type_id') || $has('badge_type_id')) {
            $need['b'] = $need['bt'] = true;
        }

        if ($has('access_level_type_id') || $has('access_level_type_name') || $has('is_printable')) {
            $need['b'] = $need['bt'] = $need['al'] = $need['a'] = true;
            if ($has('is_printable') && (string)$val('is_printable') === '1') {
                $this->joinCatalog['a'][1]  = 'join';
                $this->joinCatalog['bt'][1] = 'join';
                $this->joinCatalog['al'][1] = 'join';
            }
        }

        if ($has('badge_features_id')) {
            $need['b'] = $need['bt'] = $need['bf'] = $need['bt_bf'] = true;
        }

        if ($has('has_badge_prints') || $ord('badge_prints_count')) {
            $need['b'] = $need['prt'] = true;
        }

        if ($has('has_requested_refund_requests') || $ord('refunded_amount') || $ord('final_amount_adjusted')) {
            $need['rr'] = true;
        }

        if ($has('view_type_id')) {
            $need['b'] = $need['bt'] = $need['avt'] = true;
        }

        // --- Orders ---
        if ($ord('owner_first_name') || $ord('owner_last_name') || $ord('owner_name')) {
            $need['a'] = $need['m'] = true;
        }
        if ($ord('owner_company')) { $need['a'] = $need['a_c'] = true; }
        if ($ord('owner_email'))   { $need['a'] = $need['m']   = true; }
        if ($ord('promo_code'))    { $need['pc'] = true; }

        return array_keys($need);
    }


    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitAttendeeTicket::class;
    }

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @param Order|null $order
     * @return QueryBuilder
     */
    protected function applyExtraSelects(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null):QueryBuilder{

        $needsAggregation = false;

        if ($order) {
            if ($order->hasOrder('final_amount')) {
                $query->addSelect("(e.raw_cost - e.discount) AS HIDDEN HIDDEN_FINAL_AMOUNT");
            }
            if ($order->hasOrder('refunded_amount')) {
                $query->addSelect("COALESCE(SUM(rr.refunded_amount),0) AS HIDDEN HIDDEN_REFUNDED_AMOUNT");
                $needsAggregation = true;
            }
            if ($order->hasOrder('final_amount_adjusted')) {
                $query->addSelect("((e.raw_cost - e.discount) - COALESCE(SUM(rr.refunded_amount),0)) AS HIDDEN HIDDEN_FINAL_AMOUNT_ADJUSTED");
                $needsAggregation = true;
            }
            if ($order->hasOrder('badge_prints_count')) {
                $query->addSelect("COUNT(prt.id) AS HIDDEN HIDDEN_BADGE_PRINTS_COUNT");
                $needsAggregation = true;
            }
        }

        if ($needsAggregation) {
            $query->groupBy('e.id');
        }

        return $query;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        $args = func_get_args();
        $filter = count($args) > 0 ? $args[0] : null;
        $owner_member_id = 0;
        $owner_member_email = null;

        if($filter instanceof Filter) {
            if ($filter->hasFilter("owner_member_id")) {
                $owner_member_id = $filter->getValue("owner_member_id")[0];
            }
            if ($filter->hasFilter("owner_member_email")) {
                $owner_member_email = $filter->getValue("owner_member_email")[0];
            }
        }

        return [
            'id' => new DoctrineInFilterMapping('e.id'),
            'not_id' => new DoctrineNotInFilterMapping('e.id'),
            'number' => 'e.number:json_string',
            'is_active' => 'e.is_active',
            'order_number' => 'o.number:json_string',
            'owner_name' => "COALESCE(LOWER(CONCAT(a.first_name, ' ', a.surname)),LOWER(CONCAT(m.first_name, ' ', m.last_name)))",
            'owner_company' => 'COALESCE(a.company_name, a_c.name)',
            'has_owner_company' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "((a.company_name is not null and a.company_name <> '') OR (a_c.name is not null and a_c.name <> ''))"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "((a.company_name is null OR a.company_name = '') AND (a_c.name is null OR a_c.name = ''))"
                    ),
                ]
            ),
            'owner_first_name' => "COALESCE(LOWER(a.first_name),LOWER(m.first_name))",
            'owner_last_name' => "COALESCE(LOWER(a.surname),LOWER(m.last_name))",
            'owner_email' => [
                'm.email:json_string',
                'm.second_email:json_string',
                'm.third_email:json_string',
                'm2.email:json_string',
                'm2.second_email:json_string',
                'm2.third_email:json_string',
                'a.email:json_string'
            ],
            'summit_id' => 's.id:json_int',
            'order_owner_id' => 'COALESCE(ord_m.id,0):json_int',
            'owner_id' => 'a.id:json_int',
            'member_id' => ['m.id:json_int'],
            'order_id' => 'o.id:json_int',
            'status' => 'e.status:json_string',
            'promo_code_id' => 'pc.id:json_int',
            'promo_code_description' => 'pc.description:json_string',
            'promo_code' => 'pc.code:json_string',
            'promo_code_tag_id' => 'pct.id:json_int',
            'promo_code_tag' => 'pct.tag:json_string',
            'has_requested_refund_requests' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf
                        (
                            "EXISTS (select rr1 from %s rr1 where rr1.ticket = e and rr1.status = '%s')",
                            SummitAttendeeTicketRefundRequest::class,
                            ISummitRefundRequestConstants::RefundRequestedStatus
                        )
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        sprintf
                        (
                            "NOT EXISTS (select rr1 from %s rr1 where rr1.ticket = e and rr1.status = '%s')"
                            ,
                            SummitAttendeeTicketRefundRequest::class,
                            ISummitRefundRequestConstants::RefundRequestedStatus
                        )
                    ),
                ]
            ),
            'access_level_type_name' => 'al.name :operator :value',
            'access_level_type_id' => 'al.id :operator :value',
            'ticket_type_id' => 'tt.id:json_int',
            'view_type_id' => 'avt.id:json_int',
            'has_owner' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "a is not null"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "a is null"
                    ),
                ]
            ),
            'has_order_owner' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "ord_m is not null"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "ord_m is null"
                    ),
                ]
            ),
            'has_badge' => new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "b is not null"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "b is null"
                    ),
                ]
            ),
            'final_amount' =>  "(e.raw_cost - e.discount) :operator :value",
            'is_printable' =>
                new DoctrineSwitchFilterMapping([
                        '1' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf("(e.is_active = 1 and al.name = '%s' and a is not null)", SummitAccessLevelType::IN_PERSON),
                        ),
                        '0' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf("not(e.is_active = 1 and al.name = '%s' and a is not null)", SummitAccessLevelType::IN_PERSON),
                        ),
                    ]
                ),
            'badge_type_id' => 'bt.id:json_int',
            'has_badge_prints' =>  new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        "SIZE(prt) > 0"
                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        "SIZE(prt) = 0"
                    ),
                ]
            ),
            'badge_prints_count' => 'SIZE(prt) :operator :value',
            'exclude_is_printable_free_unassigned' =>    new DoctrineSwitchFilterMapping([
                    '1' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf
                        (
                            "NOT EXISTS ( select e2 from %s e2 ".
                            " left join e2.owner a2 ".
                            " left join e2.badge b2 ".
                            " left join b2.type bt2 ".
                            " left join bt2.access_levels al2 ".
                            " where e2.id = e.id and al2.name = '%s' and a2 is null ".
                            " and (e2.raw_cost - e2.discount) = 0 )",
                            $this->getBaseEntity(),
                            SummitAccessLevelType::IN_PERSON
                        ),

                    ),
                    '0' => new DoctrineCaseFilterMapping(
                        'false',
                        " "
                    ),
                ]
            ),
            'owner_status' => 'a.status:json_string',
            'badge_features_id' => ['bf.id:json_int','bt_bf.id:json_int'],
            'assigned_to' => new DoctrineSwitchFilterMapping([
                    'Me' => new DoctrineCaseFilterMapping(
                        'Me',
                        sprintf
                        (
                            "( a is not null and ( m.id = %s or a.email = '%s' ) )",
                            $owner_member_id,
                            $owner_member_email
                        ),
                    ),
                    'SomeoneElse' => new DoctrineCaseFilterMapping(
                        'SomeoneElse',
                        sprintf
                        (
                            "( a is not null and m.id <> %s and a.email <> '%s' )",
                            $owner_member_id,
                            $owner_member_email
                        ),
                    ),
                    'Nobody' => new DoctrineCaseFilterMapping(
                        'Nobody',
                        "a is null"
                    ),
                ]
            ),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null)
    {
        $this->joinCatalog['a'][1]  = 'leftJoin';
        $this->joinCatalog['bt'][1] = 'leftJoin';
        $this->joinCatalog['al'][1] = 'leftJoin';
        $this->joinCatalog['ord_m'][1] = 'leftJoin';

        foreach ($this->requiredAliases($filter, $order) as $alias) {
            $this->ensureJoin($query, $alias);
        }


        return $query;
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            'created' => 'e.created',
            'number' => 'e.number',
            'status' => 'e.status',
            'owner_first_name' => 'COALESCE(LOWER(a.first_name), LOWER(m.first_name))',
            'owner_last_name' => 'COALESCE(LOWER(a.surname), LOWER(m.last_name))',
            "owner_name" => <<<SQL
COALESCE(LOWER(CONCAT(a.first_name, ' ', a.surname)),LOWER(CONCAT(m.first_name, ' ', m.last_name)))
SQL,
            'ticket_type' => 'tt.name',
            'final_amount' => 'HIDDEN_FINAL_AMOUNT',
            'owner_email' => 'COALESCE(LOWER(m.email), LOWER(m.second_email), LOWER(m.third_email), LOWER(a.email))',
            'owner_company' => 'COALESCE(a.company_name, a_c.name)',
            'promo_code' => 'pc.code',
            'bought_date' => 'e.bought_date',
            'refunded_amount' => 'HIDDEN_REFUNDED_AMOUNT',
            'final_amount_adjusted' => 'HIDDEN_FINAL_AMOUNT_ADJUSTED',
            'badge_type_id' => 'bt.id',
            'badge_type' => 'bt.name',
            'badge_prints_count' => 'HIDDEN_BADGE_PRINTS_COUNT',
        ];
    }

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket
     */
    public function getByExternalOrderIdAndExternalAttendeeId($external_order_id, $external_attendee_id)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        $tickets = $query
            ->where('e.external_order_id = :external_order_id')
            ->andWhere('e.external_attendee_id = :external_attendee_id')
            ->setParameter('external_order_id', $external_order_id)
            ->setParameter('external_attendee_id', $external_attendee_id)->getQuery()->getResult();

        return count($tickets) > 0 ? $tickets[0] : null;
    }

    /**
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket
     */
    public function getByExternalOrderIdAndExternalAttendeeIdExclusiveLock
    (
        $external_order_id,
        $external_attendee_id
    ): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("e.owner", "ow")
            ->where('o.external_id = :external_order_id')
            ->andWhere('ow.external_id = :external_attendee_id')
            ->setParameter('external_order_id', $external_order_id)
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    public function delete($entity)
    {
        $this->getEntityManager()->getConnection()->delete("
        SummitAttendeeTicket
        ", ["ID" => $entity->getIdentifier()]);
    }

    /**
     * @param string $number
     * @return bool
     */
    public function existNumber(string $number): bool
    {
        return $this->count(['number' => $number]) > 0;
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByHashExclusiveLock(string $hash): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.hash = :hash')
            ->setParameter('hash', trim($hash))->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByNumberExclusiveLock(string $number): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.number = :number')
            ->setParameter('number', trim($number))->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $number
     * @return SummitAttendeeTicket|null
     */
    public function getByNumber(string $number): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.number = :number')
            ->setParameter('number', trim($number))->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket|null
     */
    public function getByFormerHashExclusiveLock(string $hash): ?SummitAttendeeTicket
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.former_hashes", "fh")
            ->where('fh.hash = :hash')
            ->setParameter('hash', trim($hash))->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $external_order_id
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock
    (
        Summit $summit,
               $external_order_id,
               $external_attendee_id
    ): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("o.summit", "s")
            ->join("e.owner", "ow")
            ->where('e.external_order_id = :external_order_id')
            ->andWhere('e.external_attendee_id = :external_attendee_id')
            ->andWhere('s.id = :summit_id')
            ->setParameter('external_order_id', $external_order_id)
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->setParameter('summit_id', $summit->getId())
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     */
    public function getByExternalAttendeeIdExclusiveLock(Summit $summit, string $external_attendee_id): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("o.summit", "s")
            ->join("e.owner", "ow")
            ->where('ow.external_id = :external_attendee_id')
            ->andWhere('s.id = :summit_id')
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->setParameter('summit_id', $summit->getId())
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }

    /**
     * @param Summit $summit
     * @param string $external_attendee_id
     * @return SummitAttendeeTicket|null
     */
    public function getByExternalAttendeeId(Summit $summit, string $external_attendee_id): ?SummitAttendeeTicket
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        return $query
            ->join("e.order", "o")
            ->join("o.summit", "s")
            ->join("e.owner", "ow")
            ->where('ow.external_id = :external_attendee_id')
            ->andWhere('s.id = :summit_id')
            ->setParameter('external_attendee_id', $external_attendee_id)
            ->setParameter('summit_id', $summit->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraFilters(QueryBuilder $query)
    {
        $query = $query->andWhere("e.status <> :cancelled")->setParameter("cancelled", IOrderConstants::CancelledStatus);
        return $query;
    }

    public function getAllTicketsIdsByOrder(int $order_id, PagingInfo $paging_info): array
    {

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e.id")
            ->from($this->getBaseEntity(), "e")
            ->join("e.order", "o")
            ->where('o.id = :order_id')
            ->setParameter("order_id", $order_id);

        $query= $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $res = $query->getQuery()->getArrayResult();
        return array_column($res, 'id');
    }


    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null){
        $start = time();
        Log::debug(sprintf('DoctrineSummitAttendeeTicketRepository::getAllByPage'));
        $total = $this->getFastCount($filter, $order);
        $ids = $this->getAllIdsByPage($paging_info, $filter, $order);
        $query = $this->getEntityManager()->createQueryBuilder()
                        ->select('e, a, o, tt, pc, b, bt, a_c, m')
                    ->from($this->getBaseEntity(), 'e')
                    ->leftJoin('e.owner', 'a')->addSelect('a')
                    ->leftJoin('e.order', 'o')->addSelect('o')
                    ->leftJoin('e.ticket_type', 'tt')->addSelect('tt')
                    ->leftJoin('e.promo_code', 'pc')->addSelect('pc')
                    ->leftJoin('e.badge', 'b')->addSelect('b')
                    ->leftJoin('b.type', 'bt')->addSelect('bt')
                    ->leftJoin('a.company', 'a_c')->addSelect('a_c')
                    ->leftJoin('a.member', 'm')->addSelect('m')
                    ->where('e.id IN (:ids)')
                    ->setParameter('ids', $ids);


        $rows = $query->getQuery()->getResult();
        $byId = [];
        foreach ($rows as $e) $byId[$e->getId()] = $e;

        $data = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) $data[] = $byId[$id];
        }

        $end = time() - $start;
        Log::debug(sprintf('DoctrineSummitAttendeeTicketRepository::getAllByPage %s seconds', $end));
        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }
}
