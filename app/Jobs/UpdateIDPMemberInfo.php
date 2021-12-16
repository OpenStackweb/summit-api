<?php

namespace App\Jobs;

use App\Services\Model\IMemberService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;

class UpdateIDPMemberInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $user_email;
    /**
     * @var string
     */
    public $user_first_name;
    /**
     * @var string
     */
    public $user_last_name;
    /**
     * @var string
     */
    public $user_company_name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SummitAttendee $attendee)
    {
        $this->user_email = $attendee->getEmail();
        $this->user_first_name = $attendee->getFirstName();
        $this->user_last_name = $attendee->getSurname();
        $this->user_company_name = $attendee->getCompanyName();
    }

    /**
     * @param IMemberService $service
     */
    public function handle(IMemberService $service)
    {
        Log::debug(sprintf("UpdateIDPMemberInfo::handle user updated %s %s", $this->user_email));

        try {
            //Check if user exists
            $user = $service->checkExternalUser($this->user_email);

            //If user exists => update it
            if (!is_null($user))
            {
                $service->updateExternalUser($user);
                return;
            }

            //If user doesn't exist => check if there is a pending registration request, if so => update it
            //...
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
    }
}
