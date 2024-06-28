<?php namespace App\Models\Foundation\Main\Strategies;
/**
 * Copyright 2024 OpenStack Foundation
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
use LaravelDoctrine\ORM\Facades\Registry;
use Libs\Utils\Doctrine\DoctrineStatementValueBinder;
use models\summit\Summit;
use models\utils\SilverstripeBaseModel;
/**
 * Class MemberSummitStrategy
 * @package App\Models\Foundation\Main\Strategies
 */
class MemberSummitStrategy implements IMemberSummitStrategy
{
    private $member_id;

    /**
     * @param $member_id
     */
    public function __construct($member_id)
    {
        $this->member_id = $member_id;
    }

    /**
     * @return array
     */
    public function getAllAllowedSummitIds(): array
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);

        $sql = <<<SQL
SELECT DISTINCT(SummitAdministratorPermissionGroup_Summits.SummitID) 
FROM SummitAdministratorPermissionGroup_Members 
INNER JOIN SummitAdministratorPermissionGroup_Summits ON 
SummitAdministratorPermissionGroup_Summits.SummitAdministratorPermissionGroupID = SummitAdministratorPermissionGroup_Members.SummitAdministratorPermissionGroupID
WHERE SummitAdministratorPermissionGroup_Members.MemberID = :member_id
SQL;

        $stmt = DoctrineStatementValueBinder::bind(
            $em->getConnection()->prepare($sql),
            [
                'member_id' => $this->member_id,
            ]
        );

        $res = $stmt->executeQuery();
        return $res->fetchFirstColumn();
    }

    /**
     * @param Summit $summit
     * @return bool
     */
    public function isSummitAllowed(Summit $summit): bool
    {
        try {
            $em = Registry::getManager(SilverstripeBaseModel::EntityManager);

            $sql = <<<SQL
SELECT COUNT(SummitAdministratorPermissionGroup_Summits.SummitID) 
FROM SummitAdministratorPermissionGroup_Members 
INNER JOIN SummitAdministratorPermissionGroup_Summits ON 
SummitAdministratorPermissionGroup_Summits.SummitAdministratorPermissionGroupID = SummitAdministratorPermissionGroup_Members.SummitAdministratorPermissionGroupID
WHERE SummitAdministratorPermissionGroup_Members.MemberID = :member_id 
  AND SummitAdministratorPermissionGroup_Summits.SummitID = :summit_id
SQL;

            $stmt = DoctrineStatementValueBinder::bind(
                $em->getConnection()->prepare($sql),
                [
                    'member_id' => $this->member_id,
                    'summit_id' => $summit->getId(),
                ]
            );
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
            return intval($res[0]) > 0;
        } catch (\Exception $ex) {
            return false;
        }
    }
}