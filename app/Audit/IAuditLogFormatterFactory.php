<?php namespace App\Audit;

interface IAuditLogFormatterFactory
{
    public function make(AuditContext $ctx, $subject, $eventType): ?IAuditLogFormatter;
}
