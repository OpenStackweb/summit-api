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

use App\Models\Foundation\Summit\IStatsConstants;
use Illuminate\Support\Facades\Log;
use models\utils\SilverstripeBaseModel;
use DateTime;
use DateTimeZone;
use utils\PagingInfo;
use utils\PagingResponse;

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
     * @param int $offset
     * @return string
     */
    private static function getOffsetFormat(int $offset):string{
        return ($offset > 0 ? '+': '-') . sprintf("%02d",  abs($offset) ).':00';
    }

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

    private function getTimeZoneOffset(){
        try {
            $tz = $this->getTimeZone();
            $offset = $tz->getOffset($this->getBeginDate());
            Log::debug(sprintf("Summit::getTimeZoneOffset offset %s", $offset));
            return $offset / 3600;
        }
        catch (\Exception $ex){
            $offset = 0;
        }
        return $offset;
    }
    /**
     * @param string $sql
     * @param string $table
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @param string $field
     * @return string
     * @throws \Exception
     */
    private static function addDatesFilteringWithTimeZone(string $sql, string $table, string $column = 'Created',
                                                          ?DateTime $startDate  = null, ?DateTime $endDate = null):string{
        if(!is_null($startDate)){
            $offset1 = self::getDefaultTimeZoneOffset($startDate);
            if(!is_null($endDate)) {
                $offset2 = self::getDefaultTimeZoneOffset($endDate);
                $sql .= sprintf(
                    " AND {$table}.{$column} BETWEEN CONVERT_TZ('%s','+00:00','%s:00') AND CONVERT_TZ('%s','+00:00','%s:00')",
                    $startDate->format("Y-m-d H:i:s"),
                    $offset1,
                    $endDate->format("Y-m-d H:i:s"),
                    $offset2
                );
            }
            else{
                $sql .= sprintf(
                    " AND {$table}.{$column} >= CONVERT_TZ('%s','+00:00','%s:00')",
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitOrder", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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
INNER JOIN SummitAttendeeTicketRefundRequest on SummitAttendeeTicketRefundRequest.ID = SummitRefundRequest.ID
INNER JOIN SummitAttendeeTicket on SummitAttendeeTicket.ID = SummitAttendeeTicketRefundRequest.TicketID
INNER JOIN SummitOrder on SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE
      SummitRefundRequest.Status='Approved' AND 
      SummitOrder.SummitID = :summit_id
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitRefundRequest", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            return $res;
        } catch (\Exception $ex) {
        }
        return 0.0;
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
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);
            $sql .= ' GROUP BY SummitTicketType.Name';
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
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
    public function getCheckedInActiveTicketsCountPerTicketType(?DateTime $startDate  = null, ?DateTime $endDate = null): array
    {
        try {
            $sql = <<<SQL
SELECT SummitTicketType.Name AS type, COUNT(SummitAttendeeTicket.ID) as qty FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitTicketType ON SummitAttendeeTicket.TicketTypeID = SummitTicketType.ID
INNER JOIN SummitAttendee ON SummitAttendee.ID = SummitAttendeeTicket.OwnerID
INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.TicketID = SummitAttendeeTicket.ID
INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitAttendeeBadge.BadgeTypeID
INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitBadgeTypeID = SummitBadgeType.ID
INNER JOIN SummitAccessLevelType ON SummitAccessLevelType.ID = SummitBadgeType_AccessLevels.SummitAccessLevelTypeID
WHERE
      SummitOrder.SummitID = :summit_id AND
      SummitAttendeeTicket.IsActive = 1 AND
      SummitAttendeeTicket.Status = 'Paid' AND
      SummitAttendee.SummitHallCheckedIn = 1 AND
      SummitAccessLevelType.Name = 'IN_PERSON'
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);
            $sql .= ' GROUP BY SummitTicketType.Name';
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
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
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeBadge", "Created", $startDate, $endDate);
            $sql .= ' GROUP BY SummitBadgeType.ID';
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
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
    public function getActiveCheckedInBadgesCountPerBadgeType(?DateTime $startDate  = null, ?DateTime $endDate = null): array
    {
        try {
            $sql = <<<SQL
SELECT SummitBadgeType.Name as type, COUNT(DISTINCT(SummitAttendeeBadge.ID)) as qty FROM SummitAttendeeBadge
INNER JOIN SummitBadgeType ON SummitAttendeeBadge.BadgeTypeID = SummitBadgeType.ID
INNER JOIN SummitAttendeeTicket ON SummitAttendeeTicket.ID = SummitAttendeeBadge.TicketID
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
INNER JOIN SummitTicketType ON SummitAttendeeTicket.TicketTypeID = SummitTicketType.ID
INNER JOIN SummitAttendee ON SummitAttendee.ID = SummitAttendeeTicket.OwnerID
INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitBadgeTypeID = SummitBadgeType.ID
INNER JOIN SummitAccessLevelType ON SummitAccessLevelType.ID = SummitBadgeType_AccessLevels.SummitAccessLevelTypeID
WHERE SummitOrder.SummitID = :summit_id AND
      SummitAttendeeTicket.IsActive = 1 AND
      SummitAttendeeTicket.Status = 'Paid' AND
      SummitAttendee.SummitHallCheckedIn = 1 AND
      SummitAccessLevelType.Name = 'IN_PERSON'
SQL;
            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeBadge", "Created", $startDate, $endDate);
            $sql .= ' GROUP BY SummitBadgeType.ID';
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
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

            if(!is_null($startDate)){
                if(!is_null($endDate)) {
                    $sql .= sprintf(
                        " AND SummitAttendee.SummitHallCheckedInDate BETWEEN '%s' AND '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                        $endDate->format("Y-m-d H:i:s"),
                    );
                }
                else{
                    $sql .= sprintf(
                        " AND SummitAttendee.SummitHallCheckedInDate >= '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                    );
                }
            }

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendee", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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
                        " AND SummitAttendee.SummitVirtualCheckedInDate BETWEEN '%s' AND '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                        $endDate->format("Y-m-d H:i:s"),
                    );
                }
                else{
                    $sql .= sprintf(
                        " AND SummitAttendee.SummitVirtualCheckedInDate >= '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                    );
                }
            }

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendee", "Created", $startDate, $endDate);

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();
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
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);
            $sql .= ' GROUP BY SummitBadgeFeatureType.Name';
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
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
SQL;

            $sql = self::addDatesFilteringWithTimeZone($sql, "SummitAttendeeTicket", "Created", $startDate, $endDate);
            $sql .= ' GROUP BY SummitBadgeFeatureType.Name';
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
            $res = count($res) > 0 ? $res : [];
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

    /**
     * @param string $groupBy
     * @param int $page
     * @param int $per_page
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return PagingResponse
     */
    public function getAttendeesCheckinsGroupedBy
    (
        string $groupBy, PagingInfo $pagingInfo, ?DateTime $startDate  = null, ?DateTime $endDate = null
    ): PagingResponse
    {
        $page = $pagingInfo->getCurrentPage();
        $per_page = $pagingInfo->getPerPage();

        try {
            $date_format = "";
            switch ($groupBy) {
                case IStatsConstants::GroupByDay:
                    $date_format = "%b %D";
                    break;
                case IStatsConstants::GroupByHour:
                    $date_format = "%m/%d %h %p";
                    break;
                case IStatsConstants::GroupByMinute:
                    $date_format = "%m/%d %h:%i %p";
                    break;
            }

            $offset = self::getOffsetFormat($this->getTimeZoneOffset());

            $sql = <<<SQL
SELECT COUNT(SummitAttendee.ID) as qty, 
       DATE_FORMAT(CONVERT_TZ(SummitAttendee.SummitHallCheckedInDate,'+00:00','{$offset}'), '{$date_format}') AS label 
FROM SummitAttendee
WHERE
SummitID = :summit_id AND
SummitHallCheckedInDate IS NOT NULL
SQL;

            // date filtering
            if(!is_null($startDate)){
                if(!is_null($endDate)) {
                    $sql .= sprintf(
                        " AND SummitAttendee.SummitHallCheckedInDate BETWEEN '%s' AND '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                        $endDate->format("Y-m-d H:i:s"),
                    );
                }
                else{
                    $sql .= sprintf(
                        " AND SummitAttendee.SummitHallCheckedInDate >= '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                    );
                }
            }
            // group by
            $sql .= " GROUP BY DATE_FORMAT(CONVERT_TZ(SummitAttendee.SummitHallCheckedInDate,'+00:00','{$offset}'), '{$date_format}')";
            $sql .= " ORDER BY SummitAttendee.SummitHallCheckedInDate ASC";

            $count_sql = <<<SQL
SELECT COUNT(*) FROM ({$sql}) T1
SQL;
            $stmt = $this->prepareRawSQL($count_sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();

            $total = count($res) > 0 ? intval($res[0]) : 0;

            $sql .= " LIMIT {$pagingInfo->getPerPage()} OFFSET {$pagingInfo->getOffset()} ";
            Log::debug(sprintf("Summit::getAttendeesCheckinsGroupedBy sql %s", $sql));
            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
            $res = count($res) > 0 ? $res : [];

            return new PagingResponse
            (
                $total,
                $per_page,
                $page,
                intval(ceil($total / $per_page)),
                $res
            );
        } catch (\Exception $ex) {
            Log::error($ex);
        }
        return new PagingResponse
        (
            0,
            $per_page,
            $page,
            1,
            []
        );
    }

    /**
     * @param string $groupBy
     * @param int $page
     * @param int $per_page
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return PagingResponse
     */
    public function getPurchasedTicketsGroupedBy
    (
        string $groupBy, PagingInfo $pagingInfo, ?DateTime $startDate  = null, ?DateTime $endDate = null
    ): PagingResponse
    {
        $page = $pagingInfo->getCurrentPage();
        $per_page = $pagingInfo->getPerPage();

        try {
            $date_format = "";
            switch ($groupBy) {
                case IStatsConstants::GroupByDay:
                    $date_format = "%b %D";
                    break;
                case IStatsConstants::GroupByHour:
                    $date_format = "%m/%d %h %p";
                    break;
                case IStatsConstants::GroupByMinute:
                    $date_format = "%m/%d %h:%i %p";
                    break;
            }

            $offset = self::getOffsetFormat($this->getTimeZoneOffset());

            $sql = <<<SQL
SELECT COUNT(SummitAttendeeTicket.ID) as qty, 
       DATE_FORMAT(CONVERT_TZ(SummitAttendeeTicket.TicketBoughtDate,'+00:00','{$offset}'), '{$date_format}') AS label 
FROM SummitAttendeeTicket
INNER JOIN SummitOrder ON SummitOrder.ID = SummitAttendeeTicket.OrderID
WHERE
SummitOrder.SummitID = :summit_id AND
SummitAttendeeTicket.IsActive = 1 AND 
SummitAttendeeTicket.Status = 'Paid'
SQL;

            // date filtering
            if(!is_null($startDate)){
                if(!is_null($endDate)) {
                    $sql .= sprintf(
                        " AND SummitAttendeeTicket.TicketBoughtDate BETWEEN '%s' AND '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                        $endDate->format("Y-m-d H:i:s"),
                    );
                }
                else{
                    $sql .= sprintf(
                        " AND SummitAttendeeTicket.TicketBoughtDate >= '%s'",
                        $startDate->format("Y-m-d H:i:s"),
                    );
                }
            }
            // group by
            $sql .= " GROUP BY DATE_FORMAT(CONVERT_TZ(SummitAttendeeTicket.TicketBoughtDate,'+00:00','{$offset}'), '{$date_format}')";
            $sql .= " ORDER BY SummitAttendeeTicket.TicketBoughtDate ASC";

            $count_sql = <<<SQL
SELECT COUNT(*) FROM ({$sql}) T1
SQL;
            $stmt = $this->prepareRawSQL($count_sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchFirstColumn();

            $total = count($res) > 0 ? intval($res[0]) : 0;

            $sql .= " LIMIT {$pagingInfo->getPerPage()} OFFSET {$pagingInfo->getOffset()} ";
            Log::debug(sprintf("Summit::getTicketsPurchasedGroupedBy sql %s", $sql));

            $stmt = $this->prepareRawSQL($sql, ['summit_id' => $this->id]);
            $res = $stmt->executeQuery();
            $res = $res->fetchAllAssociative();
            $res = count($res) > 0 ? $res : [];

            return new PagingResponse
            (
                $total,
                $per_page,
                $page,
                intval(ceil($total / $per_page)),
                $res
            );
        } catch (\Exception $ex) {
            Log::error($ex);
        }
        return new PagingResponse
        (
            0,
            $per_page,
            $page,
            1,
            []
        );
    }
}
