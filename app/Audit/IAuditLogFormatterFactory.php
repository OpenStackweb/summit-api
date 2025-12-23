<?php namespace App\Audit;

interface IAuditLogFormatterFactory
{
    public function make(AuditContext $ctx, $subject, string $event_type): ?IAuditLogFormatter;
}
