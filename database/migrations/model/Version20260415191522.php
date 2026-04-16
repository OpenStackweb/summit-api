<?php namespace Database\Migrations\Model;
/**
 * Copyright 2026 OpenStack Foundation
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

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;

/**
 * Class Version20260415191522
 * @package Database\Migrations\Model
 *
 * Backfills SummitPromoCodeMemberReservation from existing committed tickets
 * so the per-member QuantityPerAccount counter starts consistent with
 * history. Must run AFTER Version20260415191521, which creates the table.
 *
 * Deployment note: orders in flight during the backfill window may be
 * miscounted by at most their own qty — prefer a quiet window.
 */
final class Version20260415191522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill SummitPromoCodeMemberReservation from existing committed tickets.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$builder->hasTable('SummitPromoCodeMemberReservation')) {
            // Upstream migration didn't run — skip rather than crash.
            return;
        }

        $this->addSql(<<<SQL
INSERT INTO SummitPromoCodeMemberReservation (PromoCodeID, MemberID, QtyUsed, Created, LastEdited)
SELECT t.PromoCodeID,
       o.OwnerID,
       COUNT(*) AS qty,
       NOW(),
       NOW()
FROM SummitAttendeeTicket t
INNER JOIN SummitOrder o ON o.ID = t.OrderID
WHERE t.PromoCodeID IS NOT NULL
  AND o.OwnerID IS NOT NULL
  AND o.Status IN ('Reserved', 'Paid', 'Confirmed')
  AND t.Status != 'Cancelled'
GROUP BY t.PromoCodeID, o.OwnerID
ON DUPLICATE KEY UPDATE QtyUsed = VALUES(QtyUsed), LastEdited = NOW()
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable('SummitPromoCodeMemberReservation')) {
            $this->addSql('TRUNCATE TABLE SummitPromoCodeMemberReservation');
        }
    }
}
