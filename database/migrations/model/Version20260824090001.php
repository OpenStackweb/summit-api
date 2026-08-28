<?php namespace Database\Migrations\Model;
/*
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
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Summit;
use models\utils\SilverstripeBaseModel;

/**
 * Per-summit backfill for the CFP reopen notification email event.
 *
 * Version20260824090000 registers the SummitEmailEventFlowType (the row that makes the email
 * SEND, since Summit::getEmailIdentifierPerEmailEventFlowSlug() falls back to the type's default
 * template). This migration adds the per-summit SummitEmailEventFlow row that makes the event
 * VISIBLE and overridable on each existing show's Email Flow Events page
 * (GET /summits/{id}/email-flows-events lists SummitEmailEventFlow rows, not types).
 *
 * Companion to the type migration, following the repo convention that every new event type ships
 * with a seedDefaultEmailFlowEvents() backfill -- precedent: Version20250812201257 (type) paired with
 * Version20250812201307 (backfill), which this copies. SummitEmailFlowEventSeeder does the same
 * thing but the k8s deploy flow does not run seeders, so on a deployed database this migration is
 * the only path that creates the rows.
 *
 * Idempotent: Summit::seedDefaultEmailFlowEvents() creates a row only when getEmailEventByType()
 * returns null, so a re-run is a no-op and existing per-summit overrides are never touched.
 *
 * Ordered after Version20260824090000 so the type row exists when this runs; on a fresh install
 * both are no-ops (no summits, no types yet) and the seeders take over.
 */
final class Version20260824090001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed seedDefaultEmailFlowEvents for all summits (CFP reopen notification)';
    }

    public function up(Schema $schema): void
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $repository = $em->getRepository(Summit::class);
        $summits = $repository->findAll();
        foreach ($summits as $summit) {
            $summit->seedDefaultEmailFlowEvents();
            $em->persist($summit);
        }
        $em->flush();
    }

    /**
     * Deliberate no-op, matching Version20250812201307 and the type migration's down(): deleting
     * SummitEmailEventFlow rows would destroy any per-summit template override an operator has
     * already authored against them.
     */
    public function down(Schema $schema): void
    {
    }
}
