<?php namespace App\Jobs\Emails\Schedule\RSVP;

use App\Services\Model\ISummitRSVPService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\SummitEvent;
use utils\FilterParser;

class ProcessRSVPConfirmationEmailsJob implements ShouldQueue
{
    public $tries = 1;

    // no timeout
    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payload;

    private $filter;

    private $summit_event_id;

    /**
     * ProcessRSVPInvitationsJob constructor.
     * @param SummitEvent $summit_event
     * @param array $payload
     * @param $filter
     */
    public function __construct(SummitEvent $summit_event, array $payload, $filter)
    {
        $this->summit_event_id = $summit_event->getId();
        $this->payload = $payload;
        $this->filter = $filter;
    }

    /**
     * @param ISummitRSVPService $service
     * @return void
     * @throws \utils\FilterParserException
     */
    public function handle(ISummitRSVPService $service){
        Log::debug(sprintf("ProcessRSVPConfirmationEmailsJob::handle summit event id %s", $this->summit_event_id));

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, [
            'id' => ['=='],
            'not_id' => ['=='],
            'owner_email' => ['@@', '=@', '=='],
            'owner_first_name' => ['@@', '=@', '=='],
            'owner_last_name' => ['@@', '=@', '=='],
            'owner_full_name' => ['@@', '=@', '=='],
            'seat_type' => ['=='],
        ]) : null;

        $service->resend($this->summit_event_id, $this->payload, $filter);

        Log::debug(sprintf("ProcessRSVPConfirmationEmailsJob::handle summit event id %s has finished", $this->summit_event_id));
    }

}