<?php namespace App\Repositories\Summit;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationInvitationRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;

/**
 * Class DoctrineSummitRegistrationInvitationRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitRegistrationInvitationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitRegistrationInvitationRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return SummitRegistrationInvitation::class;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null, ?Order $order = null){
        $query = $query->join('e.summit', 's');
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
            'email' => 'e.email:json_string',
            'first_name' => Filter::buildLowerCaseStringField('e.first_name'),
            'last_name' => Filter::buildLowerCaseStringField('e.last_name'),
            'full_name' => Filter::buildConcatStringFields(['e.first_name', 'e.last_name']),
            'is_accepted' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        sprintf("e.status = '%s'", SummitRegistrationInvitation::Status_Accepted)
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        sprintf("e.status <> '%s'", SummitRegistrationInvitation::Status_Accepted)
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
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'ticket_types_id' => new DoctrineLeftJoinFilterMapping("e.ticket_types", "tt" ,"tt.id :operator :value"),
            'tags' => new DoctrineLeftJoinFilterMapping("e.tags", "t","t.tag :operator :value"),
            'tags_id' => new DoctrineLeftJoinFilterMapping("e.tags", "t","t.id :operator :value"),
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
            'email' => 'e.email',
            'first_name' => 'e.first_name',
            'last_name' => 'e.last_name',
            'full_name'=> Filter::buildConcatStringFields(['e.first_name', 'e.last_name']),
            'status' => 'e.status',
        ];
    }

    /**
     * @param string $hash
     * @return SummitRegistrationInvitation|null
     */
    public function getByHashExclusiveLock(string $hash): ?SummitRegistrationInvitation
    {
        return $this->findOneBy(['hash'=> trim($hash)]);
    }

    /**
     * @inheritDoc
     */
    public function getAllIdsNonAcceptedPerSummit(Summit $summit): array
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e.id")
            ->from($this->getBaseEntity(), "e")
            ->join("e.summit","s")
            ->where('e.accepted_date is null')
            ->andWhere('s.id = :summit_id')->setParameter("summit_id", $summit->getId());
        return $query->getQuery()->getResult();
    }

    /**
     * @param string $hash
     * @param Summit $summit
     * @return SummitRegistrationInvitation|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByHashAndSummit(string $hash, Summit $summit): ?SummitRegistrationInvitation
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.summit","s")
            ->where('e.hash = :hash')
            ->andWhere('s.id = :summit_id')
            ->setParameter("summit_id", $summit->getId())
            ->setParameter('hash', trim($hash));

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $summit_id
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function deleteAllBySummit(int $summit_id):bool{
        try {
            $sql = <<<SQL
DELETE I FROM SummitRegistrationInvitation I WHERE I.SummitID = :summit_id;
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            return $stmt->executeStatement([
                'summit_id' => $summit_id,
            ]) > 0;

        }
        catch (\Exception $ex)
        {
            Log::error($ex);
        }
    }
}