<?php namespace App\Audit;

interface IAuditLogFormatterFactory
{
    public function make($subject, $eventType): ?IAuditLogFormatter;
}
