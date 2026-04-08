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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20260408120000
 * @package Database\Migrations\Model
 *
 * Backfills the Permissions JSON column on Sponsor_Users with the group codes
 * (Group.Code) of every group the member belongs to, derived from Group_Members.
 * Only rows where Permissions IS NULL are updated (i.e. rows that pre-date the
 * per-sponsor permission tracking feature added in Version20260402153110).
 */
final class Version20260408102410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill Sponsor_Users.Permissions JSON from existing Group_Members rows.';
    }

    public function up(Schema $schema): void
    {
        // For each Sponsor_Users row whose Permissions have not been set yet,
        // aggregate all Group.Code values for the member and store them as a
        // JSON array. JSON_ARRAYAGG returns NULL when there are no matching
        // groups, which is a valid (empty-permissions) state.
        $this->addSql(<<<SQL
UPDATE Sponsor_Users su
SET su.Permissions = (
    SELECT JSON_ARRAYAGG(g.Code)
    FROM Group_Members gm
    INNER JOIN `Group` g ON g.ID = gm.GroupID
    WHERE gm.MemberID = su.MemberID
)
WHERE su.Permissions IS NULL
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Revert the backfill by clearing all Permissions values. This is the
        // only safe inverse: we cannot distinguish backfilled values from those
        // written by the application after the migration ran.
        $this->addSql("UPDATE Sponsor_Users SET Permissions = NULL");
    }
}
