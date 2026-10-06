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

/**
 * SUP-86b9fp53j: gives SponsorBadgeScan a UNIQUE index over the identity of one
 * physical badge scan, so a client retry can never land as a second row.
 *
 * The Redis lock in SponsorUserInfoGrantService::addBadgeScanLocked cannot provide
 * that guarantee on its own: it has a TTL, no renewal and no fencing token, so it
 * can expire while the transaction it wraps is still running (DoctrineTransactionService
 * retries a root transaction up to MaxRetries = 10 on reconnectable errors, with no
 * backoff bounding the wall clock), and LockManagerService::releaseLock only logs
 * 'lock was not held by this token at release time' when that happens. This index is
 * what makes the invariant hold; the lock is now just an optimization that keeps the
 * common case from doing wasted work.
 *
 * Why a denormalized column instead of an index over (SponsorID, BadgeID, ScanDate):
 * SponsorUserInfoGrant/SponsorBadgeScan is a JOINED inheritance pair, with SponsorID
 * on the parent table and BadgeID/ScanDate on the child - a UNIQUE index cannot span
 * the two tables. SponsorBadgeScan::buildDedupKey() produces the value.
 *
 * Deliberately does NOT backfill and does NOT delete anything. The column is nullable
 * and every existing row keeps NULL; MySQL permits unlimited NULLs in a UNIQUE index,
 * so the duplicate rows this bug has already produced in production neither block the
 * CREATE nor have to be reconciled here (deleting scan rows would also mean cascading
 * into SponsorBadgeScanExtraQuestionAnswer - real lead-gen data, not this migration's
 * call to destroy). Those historical rows remain covered by the explicit
 * findExistingBadgeScan() check in the service, which matches on the real columns.
 * Only rows created from now on carry a key, and those are exactly the ones a retry
 * can race against.
 */
final class Version20260910181020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SponsorBadgeScan.ScanDedupKey with a UNIQUE index (badge scan idempotency)';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
ALTER TABLE SponsorBadgeScan ADD COLUMN ScanDedupKey VARCHAR(128) DEFAULT NULL;
SQL
        );

        $this->addSql(<<<SQL
CREATE UNIQUE INDEX SponsorBadgeScan_ScanDedupKey ON SponsorBadgeScan (ScanDedupKey);
SQL
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
DROP INDEX SponsorBadgeScan_ScanDedupKey ON SponsorBadgeScan;
SQL
        );

        $this->addSql(<<<SQL
ALTER TABLE SponsorBadgeScan DROP COLUMN ScanDedupKey;
SQL
        );
    }
}
