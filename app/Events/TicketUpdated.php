<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use models\summit\SummitAttendee;

class TicketUpdated {
  use Dispatchable, InteractsWithSockets, SerializesModels;

  protected $attendee;

  /**
   * TicketUpdated constructor.
   * @param SummitAttendee $attendee
   */
  public function __construct(SummitAttendee $attendee) {
    $this->attendee = $attendee;
  }

  public function getAttendee(): SummitAttendee {
    return $this->attendee;
  }
}
