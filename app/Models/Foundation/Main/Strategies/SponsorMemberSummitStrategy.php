<?php namespace App\Models\Foundation\Main\Strategies;

use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Summit;
use models\utils\SilverstripeBaseModel;

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

class SponsorMemberSummitStrategy implements IMemberSummitStrategy
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
SELECT DISTINCT(Sponsor.SummitID)
FROM Sponsor_Users INNER JOIN Sponsor ON Sponsor_Users.SponsorID = Sponsor.ID
WHERE Sponsor_Users.MemberID = :member_id;
SQL;

        $stmt = $em->getConnection()->prepare($sql);
        $res = $stmt->execute(
            [
                'member_id' => $this->member_id,
            ]
        );
        return $res->fetchAllNumeric();
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
SELECT COUNT(Sponsor.SummitID)
FROM Sponsor_Users INNER JOIN Sponsor ON Sponsor_Users.SponsorID = Sponsor.ID
WHERE Sponsor_Users.MemberID = :member_id
        AND Sponsor.SummitID = :summit_id
SQL;

            $stmt = $em->getConnection()->prepare($sql);
            $res = $stmt->execute(
                [
                    'member_id' => $this->member_id,
                    'summit_id' => $summit->getId(),
                ]
            );
            $res = $res->fetchFirstColumn();
            return intval($res[0]) > 0;
        } catch (\Exception $ex) {
            return false;
        }
    }
}