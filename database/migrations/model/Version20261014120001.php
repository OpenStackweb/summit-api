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
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;

/**
 * Class Version20261014120001
 * Freezes the folder segment of every modern-format (LegacyPathFormat = 0) media upload at the
 * value getPath() derives today, so a later title or summit slug change stops moving it.
 * Mirrors getPath(): the presentation slug comes from the title, or the stored slug when the
 * title is empty (generateSlug() returns early on an empty title).
 * @package Database\Migrations\Model
 */
final class Version20261014120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill PresentationMediaUpload.FrozenPathSegment for modern-format media uploads.';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(<<<SQL
SELECT pmu.ID, e.SummitID, s.Slug AS SummitSlug, e.ID AS PresentationID, e.Title, p.Slug AS PresentationSlug
FROM PresentationMediaUpload pmu
INNER JOIN PresentationMaterial pm ON pm.ID = pmu.ID
INNER JOIN Presentation p ON p.ID = pm.PresentationID
INNER JOIN SummitEvent e ON e.ID = p.ID
INNER JOIN Summit s ON s.ID = e.SummitID
WHERE pmu.LegacyPathFormat = 0 AND pmu.FrozenPathSegment IS NULL
SQL);

        foreach ($rows as $row) {
            $title = (string)($row['Title'] ?? '');
            $slug = !empty($title) ? Presentation::slugify($title) : $row['PresentationSlug'];
            $segment = PresentationMediaUpload::buildModernPathSegment(
                intval($row['SummitID']), $row['SummitSlug'], intval($row['PresentationID']), $slug
            );
            $this->addSql('UPDATE PresentationMediaUpload SET FrozenPathSegment = ? WHERE ID = ?', [$segment, intval($row['ID'])]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE PresentationMediaUpload SET FrozenPathSegment = NULL WHERE LegacyPathFormat = 0');
    }
}
