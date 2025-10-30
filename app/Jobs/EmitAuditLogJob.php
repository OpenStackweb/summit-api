<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Keepsuit\LaravelOpenTelemetry\Facades\Logger;

class EmitAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public string $logMessage;
    public array $auditData;

    public function __construct(string $logMessage, array $auditData = [])
    {
        $this->logMessage = $logMessage;
        $this->auditData = $auditData;
    }

    public function handle(): void
    {
        try {
            Logger::info($this->logMessage, $this->auditData);
        } catch (\Exception $e) {
             Log::error("EmitAuditLogJob::handle failed", [
                 'message' => $this->logMessage,
                 'error' => $e->getMessage()
             ]);
        }
    }
}