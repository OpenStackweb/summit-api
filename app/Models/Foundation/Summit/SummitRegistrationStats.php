<?php namespace models\summit;
/*
 * Copyright 2022 OpenStack Foundation
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

use Illuminate\Support\Facades\Cache;
use models\utils\SilverstripeBaseModel;
use DateTime;
use DateTimeZone;
/**
 * Trait SummitRegistrationStats
 * @package models\summit
 */
trait SummitRegistrationStats
{
    /**
     * Registration statistics
     */

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getActiveTicketsCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
          select COUNT(SummitAttendeeTicket.ID) FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.IsActive = 1 AND 
      SummitAttendeeTicket.Status = 'Paid';
SQL;

            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            return !is_null($res) ? $res : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getInactiveTicketsCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
          select COUNT(SummitAttendeeTicket.ID) FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.IsActive = 0 AND 
      SummitAttendeeTicket.Status = 'Paid';
SQL;

            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            return !is_null($res) ? $res : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getActiveAssignedTicketsCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
SELECT COUNT(SummitAttendeeTicket.ID) FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitAttendee ON SummitAttendee.ID = SummitAttendeeTicket.OwnerID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.IsActive = 1 AND 
      SummitAttendeeTicket.Status = 'Paid';
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            return !is_null($res) ? $res : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getTotalOrdersCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
    select COUNT(SummitOrder.ID) FROM SummitOrder
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitOrder.Status = 'Paid';
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitOrder.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            return !is_null($res) ? $res : 0;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return float
     */
    public function getTotalPaymentAmountCollected(?DateTime $startDate  = null, ?DateTime $endDate = null): float
    {
        $key = sprintf("%s_getTotalPaymentAmountCollected", $this->id);
        $res = floatval(Cache::get($key, 0.0));
        if ($res > 0) return $res;

        try {
            $sql = <<<SQL
     SELECT SUM(SummitAttendeeTicket.RawCost - SummitAttendeeTicket.Discount)  FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.Status = 'Paid';
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            Cache::add($key, $res, 60);
            return $res;
        } catch (\Exception $ex) {

        }
        return 0;

    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return float
     */
    public function getTotalRefundAmountEmitted(?DateTime $startDate  = null, ?DateTime $endDate = null): float
    {
        $key = sprintf("%s_getTotalRefundAmountEmitted", $this->id);
        $res = floatval(Cache::get($key, 0.0));
        if ($res > 0) return $res;

        try {
            $sql = <<<SQL
      SELECT SUM(SummitRefundRequest.RefundedAmount) FROM `SummitRefundRequest`
inner join SummitAttendeeTicketRefundRequest on SummitAttendeeTicketRefundRequest.ID = SummitRefundRequest.ID
inner join SummitAttendeeTicket on SummitAttendeeTicket.ID = SummitAttendeeTicketRefundRequest.TicketID
inner join SummitOrder on SummitOrder.ID = SummitAttendeeTicket.OrderID
where SummitRefundRequest.Status='Approved' AND 
      SummitOrder.SummitID = :summit_id;
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitRefundRequest.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            Cache::add($key, $res, 60);
            return $res;
        } catch (\Exception $ex) {
        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return array
     */
    public function getActiveTicketsCountPerTicketType(?DateTime $startDate  = null, ?DateTime $endDate = null): array
    {
        $key = sprintf("%s_getActiveTicketsCountPerTicketType", $this->id);
        $res = Cache::get($key);
        if (!empty($res)) return json_decode($res, true);

        try {
            $sql = <<<SQL
select SummitTicketType.Name AS type, COUNT(SummitAttendeeTicket.ID) as qty FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitTicketType ON SummitAttendeeTicket.TicketTypeID = SummitTicketType.ID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.IsActive = 1 AND 
      SummitAttendeeTicket.Status = 'Paid'
GROUP BY SummitTicketType.Name;
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            Cache::add($key, json_encode($res), 60);
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return array
     */
    public function getActiveBadgesCountPerBadgeType(?DateTime $startDate  = null, ?DateTime $endDate = null): array
    {
        $key = sprintf("%s_getActiveBadgesCountPerBadgeType", $this->id);
        $res = Cache::get($key);
        if (!empty($res)) return json_decode($res, true);

        try {
            $sql = <<<SQL
SELECT SummitBadgeType.Name as type, COUNT(DISTINCT(SummitAttendeeBadge.ID)) as qty FROM SummitAttendeeBadge
INNER JOIN SummitBadgeType ON SummitAttendeeBadge.BadgeTypeID = SummitBadgeType.ID
INNER JOIN SummitAttendeeTicket ON SummitAttendeeTicket.ID = SummitAttendeeBadge.TicketID
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitTicketType ON SummitAttendeeTicket.TicketTypeID = SummitTicketType.ID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.IsActive = 1 AND 
      SummitAttendeeTicket.Status = 'Paid'
GROUP BY SummitBadgeType.ID;
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeBadge.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            Cache::add($key, json_encode($res), 60);
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getCheckedInAttendeesCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        $key = sprintf("%s_getCheckedInAttendeesCount", $this->id);
        $res = intval(Cache::get($key, 0));
        if ($res > 0) return $res;

        try {
            $sql = <<<SQL
SELECT COUNT(DISTINCT(SummitAttendee.ID)) FROM SummitAttendee
WHERE 
SummitAttendee.SummitID = :summit_id AND
EXISTS ( 
    SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    WHERE 
    SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
    SummitAttendeeTicket.Status = 'Paid' AND
    SummitAttendeeTicket.IsActive = 1
) AND
SummitAttendee.SummitHallCheckedIn = 1;
SQL;

            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendee.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            Cache::add($key, $res, 60);
            return $res;
        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getNonCheckedInAttendeesCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        $key = sprintf("%s_getNonCheckedInAttendeesCount", $this->id);
        $res = intval(Cache::get($key, 0));
        if ($res > 0) return $res;

        try {
            $sql = <<<SQL
SELECT COUNT(SummitAttendee.ID) FROM SummitAttendee
WHERE SummitAttendee.SummitID = :summit_id AND
EXISTS ( 
    SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    WHERE SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
    SummitAttendeeTicket.Status = 'Paid' AND 
    SummitAttendeeTicket.IsActive = 1      
)
AND
SummitAttendee.SummitHallCheckedIn = 0;
SQL;

            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendee.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            Cache::add($key, $res, 60);
            return $res;

        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return int
     */
    public function getVirtualAttendeesCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        $key = sprintf("%s_getVirtualAttendeesCount", $this->id);
        $res = intval(Cache::get($key, 0));
        if ($res > 0) return $res;

        try {
            $sql = <<<SQL
SELECT COUNT(ID) FROM `SummitAttendee` 
WHERE 
EXISTS ( 
    SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    WHERE SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
    SummitAttendeeTicket.Status = 'Paid' AND 
    SummitAttendeeTicket.IsActive = 1      
) AND      
SummitVirtualCheckedInDate IS NOT NULL 
AND SummitID = :summit_id;
SQL;

            if(!is_null($startDate) && !is_null($endDate)){
                $sql .= sprintf(
                    "AND SummitAttendee.SummitVirtualCheckedInDate BETWEEN '%s' AND '%s'",
                    $startDate->format("Y-m-d H:i:s"),
                    $endDate->format("Y-m-d H:i:s"),
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            Cache::add($key, $res, 60);
            return $res;

        } catch (\Exception $ex) {

        }
        return 0;
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return array
     */
    public function getActiveTicketsPerBadgeFeatureType(?DateTime $startDate  = null, ?DateTime $endDate = null): array
    {
        $key = sprintf("%s_getActiveTicketsPerBadgeFeatureType", $this->id);
        $res = Cache::get($key);
        if (!empty($res)) return json_decode($res, true);

        try {
            $sql = <<<SQL
SELECT  SummitBadgeFeatureType.Name as type, COUNT(DISTINCT(SummitAttendeeTicket.ID)) as qty FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
LEFT JOIN SummitBadgeType_BadgeFeatures ON SummitBadgeType_BadgeFeatures.SummitBadgeTypeID = SummitBadgeType.ID
LEFT JOIN SummitAttendeeBadge_Features ON SummitAttendeeBadge_Features.SummitAttendeeBadgeID = SummitAttendeeBadge.ID
INNER JOIN SummitBadgeFeatureType ON SummitBadgeFeatureType.ID = SummitAttendeeBadge_Features.SummitBadgeFeatureTypeID
OR SummitBadgeFeatureType.ID = SummitAttendeeBadge_Features.SummitBadgeFeatureTypeID
WHERE
SummitAttendeeTicket.IsActive = 1 AND
SummitAttendeeTicket.Status = 'Paid' AND
SummitOrder.SummitID = :summit_id
GROUP BY SummitBadgeFeatureType.Name;
SQL;
            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            Cache::add($key, json_encode($res), 60);
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

    /**
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return array
     */
    public function getAttendeesCheckinPerBadgeFeatureType(?DateTime $startDate  = null, ?DateTime $endDate = null): array
    {
        $key = sprintf("%s_getAttendeesCheckinPerBadgeFeatureType", $this->id);
        $res = Cache::get($key);
        if (!empty($res)) return json_decode($res, true);

        try {
            $sql = <<<SQL
SELECT  SummitBadgeFeatureType.Name as type, COUNT(DISTINCT(SummitAttendeeTicket.ID)) as qty FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
LEFT JOIN SummitBadgeType_BadgeFeatures ON SummitBadgeType_BadgeFeatures.SummitBadgeTypeID = SummitBadgeType.ID
LEFT JOIN SummitAttendeeBadge_Features ON SummitAttendeeBadge_Features.SummitAttendeeBadgeID = SummitAttendeeBadge.ID
INNER JOIN SummitBadgeFeatureType ON SummitBadgeFeatureType.ID = SummitAttendeeBadge_Features.SummitBadgeFeatureTypeID
OR SummitBadgeFeatureType.ID = SummitAttendeeBadge_Features.SummitBadgeFeatureTypeID
INNER JOIN SummitAttendee ON SummitAttendee.ID = SummitAttendeeTicket.OwnerID
WHERE
SummitAttendeeTicket.IsActive = 1 AND
SummitAttendeeTicket.Status = 'Paid' AND
SummitOrder.SummitID = :summit_id AND
SummitAttendee.SummitHallCheckedIn = 1
GROUP BY SummitBadgeFeatureType.Name;
SQL;

            if(!is_null($startDate) && !is_null($endDate)){
                $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
                $offset = $defaultSSTimeZone->getOffset(new DateTime('now', new \DateTimeZone('UTC')));
                $sql .= sprintf(
                    "AND SummitAttendeeTicket.Created BETWEEN CONVERT_TZ('%s','UTC,'%s:00') AND CONVERT_TZ('%s','UTC,'%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset
                );
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            Cache::add($key, json_encode($res), 60);
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

}