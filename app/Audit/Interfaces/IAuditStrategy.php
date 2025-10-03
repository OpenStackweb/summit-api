<?php

namespace App\Audit\Interfaces;

/**
 * Audit Strategy Interface
 * Defines contract for different audit implementations (DB, OTLP, etc.)
 */
interface IAuditStrategy
{
    /**
     * Audit an entity change
     * 
     * @param mixed $subject The entity or collection being audited
     * @param array $change_set Array of changes (field => [old, new])
     * @param string $event_type Type of audit event (create, update, delete, collection_update)
     * @return void
     */
    public function audit($subject, array $change_set, string $event_type): void;

}