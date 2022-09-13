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
     * @param DateTime|null $now
     * @return float|int
     * @throws \Exception
     */
    private static function getDefaultTimeZoneOffset(?DateTime $now = null){
        if(is_null($now))
            $now = new DateTime('now', new DateTimeZone('UTC'));
        $defaultSSTimeZone = new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone);
        return $defaultSSTimeZone->getOffset($now) / 3600;
    }

    /**
     * @param string $sql
     * @param string $table
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return string
     * @throws \Exception
     */
    private static function addDatesFilteringWithTimeZone(string $sql, string $table, ?DateTime $startDate  = null, ?DateTime $endDate = null):string{
        if(!is_null($startDate)){
            $offset1 = self::getDefaultTimeZoneOffset($startDate);
            if(!is_null($endDate)) {
                $offset2 = self::getDefaultTimeZoneOffset($endDate);
                $sql .= sprintf(
                    " AND {$table}.Created BETWEEN CONVERT_TZ('%s','+00:00','%s:00') AND CONVERT_TZ('%s','+00:00','%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset1,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset2
                );
            }
            else{
                $sql .= sprintf(
                    " AND {$table}.Created >= CONVERT_TZ('%s','+00:00','%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset1,
                );
            }
        }
        return $sql;
    }
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
      SummitAttendeeTicket.Status = 'Paid'
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);

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
      SummitAttendeeTicket.Status = 'Paid'
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);

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
      SummitAttendeeTicket.Status = 'Paid'
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);

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
      SummitOrder.Status = 'Paid'
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitOrder", $startDate, $endDate);

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
        try {
            $sql = <<<SQL
     SELECT SUM(SummitAttendeeTicket.RawCost - SummitAttendeeTicket.Discount)  FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.Status = 'Paid'
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
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
        try {
            $sql = <<<SQL
      SELECT SUM(SummitRefundRequest.RefundedAmount) FROM `SummitRefundRequest`
inner join SummitAttendeeTicketRefundRequest on SummitAttendeeTicketRefundRequest.ID = SummitRefundRequest.ID
inner join SummitAttendeeTicket on SummitAttendeeTicket.ID = SummitAttendeeTicketRefundRequest.TicketID
inner join SummitOrder on SummitOrder.ID = SummitAttendeeTicket.OrderID
where SummitRefundRequest.Status='Approved' AND 
      SummitOrder.SummitID = :summit_id
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitRefundRequest", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
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
        try {
            $sql = <<<SQL
select SummitTicketType.Name AS type, COUNT(SummitAttendeeTicket.ID) as qty FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitTicketType ON SummitAttendeeTicket.TicketTypeID = SummitTicketType.ID
WHERE SummitOrder.SummitID = :summit_id AND 
      SummitAttendeeTicket.IsActive = 1 AND 
      SummitAttendeeTicket.Status = 'Paid'
GROUP BY SummitTicketType.Name
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
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
GROUP BY SummitBadgeType.ID
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeBadge", $startDate, $endDate);
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
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
    public function getInPersonCheckedInAttendeesCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
SELECT COUNT(DISTINCT(SummitAttendee.ID)) FROM SummitAttendee
WHERE 
SummitAttendee.SummitID = :summit_id AND
EXISTS ( 
    SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
    INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
    INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitBadgeTypeID = SummitBadgeType.ID
    INNER JOIN SummitAccessLevelType ON SummitAccessLevelType.ID = SummitBadgeType_AccessLevels.SummitAccessLevelTypeID
    WHERE 
        SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
        SummitAttendeeTicket.Status = 'Paid' AND 
        SummitAttendeeTicket.IsActive = 1  AND 
        SummitAccessLevelType.Name = 'IN_PERSON' 
) AND
SummitAttendee.SummitHallCheckedIn = 1
SQL;


            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendee", $startDate, $endDate);
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
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
    public function getInPersonNonCheckedInAttendeesCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
SELECT COUNT(SummitAttendee.ID) FROM SummitAttendee
WHERE SummitAttendee.SummitID = :summit_id AND
EXISTS ( 
    SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
    INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
    INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitBadgeTypeID = SummitBadgeType.ID
    INNER JOIN SummitAccessLevelType ON SummitAccessLevelType.ID = SummitBadgeType_AccessLevels.SummitAccessLevelTypeID
    WHERE 
        SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
        SummitAttendeeTicket.Status = 'Paid' AND 
        SummitAttendeeTicket.IsActive = 1  AND 
        SummitAccessLevelType.Name = 'IN_PERSON' 
)
AND      
SummitAttendee.SummitHallCheckedIn = 0
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendee", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
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

        try {
            $sql = <<<SQL
SELECT COUNT(ID) FROM `SummitAttendee` 
WHERE 
EXISTS ( 
     SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
    INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
    INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitBadgeTypeID = SummitBadgeType.ID
    INNER JOIN SummitAccessLevelType ON SummitAccessLevelType.ID = SummitBadgeType_AccessLevels.SummitAccessLevelTypeID
    WHERE 
        SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
        SummitAttendeeTicket.Status = 'Paid' AND 
        SummitAttendeeTicket.IsActive = 1  AND 
        SummitAccessLevelType.Name = 'VIRTUAL' 
) 
AND SummitVirtualCheckedInDate IS NOT NULL 
AND SummitID = :summit_id
SQL;

            if(!is_null($startDate)){
                if(!is_null($endDate)) {
                    $sql .= sprintf(
                        "AND SummitAttendee.SummitVirtualCheckedInDate BETWEEN '%s' AND '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                        $endDate->format("Y-m-d H:i:s"),
                    );
                }
                else{
                    $sql .= sprintf(
                        "AND SummitAttendee.SummitVirtualCheckedInDate >= '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                    );
                }
            }

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            return $res;

        } catch (\Exception $ex) {

        }
        return 0;
    }


    public function getVirtualNonCheckedInAttendeesCount(?DateTime $startDate  = null, ?DateTime $endDate = null): int
    {
        try {
            $sql = <<<SQL
SELECT COUNT(SummitAttendee.ID) FROM SummitAttendee
WHERE SummitAttendee.SummitID = :summit_id AND
EXISTS ( 
    SELECT SummitAttendeeTicket.ID FROM SummitAttendeeTicket 
    INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
    INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
    INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitBadgeTypeID = SummitBadgeType.ID
    INNER JOIN SummitAccessLevelType ON SummitAccessLevelType.ID = SummitBadgeType_AccessLevels.SummitAccessLevelTypeID
    WHERE 
        SummitAttendeeTicket.OwnerID = SummitAttendee.ID AND 
        SummitAttendeeTicket.Status = 'Paid' AND 
        SummitAttendeeTicket.IsActive = 1  AND 
        SummitAccessLevelType.Name = 'VIRTUAL' 
)    
AND SummitVirtualCheckedInDate IS NULL 
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendee", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            return $res;

        }
        catch (\Exception $ex) {

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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['summit_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

}