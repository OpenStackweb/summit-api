<?php namespace App\Jobs\Emails;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Services\Apis\IMailApi;
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Class AbstractSummitAttendeeTicketEmail
 * @package App\Jobs\Emails\Registration
 */
abstract class AbstractSummitAttendeeTicketEmail extends AbstractEmailJob
{

    public function handle
    (
        IMailApi $api
    )
    {
        $memberService = App::make(IMemberService::class);

        if(isset($this->payload['summit_virtual_site_url'])){
            $this->payload['raw_summit_virtual_site_url'] = $this->payload['summit_virtual_site_url'];
        }

        if(isset($this->payload['summit_marketing_site_url'])){
            $this->payload['raw_summit_marketing_site_url'] = $this->payload['summit_marketing_site_url'];
        }

        if($memberService instanceof IMemberService){

            $email = $this->payload['owner_email'];
            // check if exist at idp
            $user = $memberService->checkExternalUser($email);

            if(is_null($user)){

                // user does not exist at idp so we need to generate a registration request
                // and create the magic links to complete the registration request
                try {

                    $userRegistrationRequest = $memberService->emitRegistrationRequest
                    (
                        $email,
                        $this->payload['owner_first_name'],
                        $this->payload['owner_last_name'],
                        $this->payload['owner_company'] ?? ''
                    );

                    $setPasswordLink = $userRegistrationRequest['set_password_link'];

                    if (isset($this->payload['summit_virtual_site_url']) &&
                        !empty($this->payload['summit_virtual_site_url']) &&
                        isset($this->payload['summit_virtual_site_oauth2_client_id']) &&
                        !empty($this->payload['summit_virtual_site_oauth2_client_id'])) {
                        $this->payload['summit_virtual_site_url'] = sprintf(
                            "%s?client_id=%s&redirect_uri=%s",
                            $setPasswordLink,
                            $this->payload['summit_virtual_site_oauth2_client_id'],
                            urlencode($this->payload['summit_virtual_site_url'])
                        );
                    }

                    if (isset($this->payload['summit_marketing_site_url']) &&
                        !empty($this->payload['summit_marketing_site_url']) &&
                        isset($this->payload['summit_marketing_site_oauth2_client_id']) &&
                        !empty($this->payload['summit_marketing_site_oauth2_client_id'])) {
                        $this->payload['summit_marketing_site_url'] = sprintf(
                            "%s?client_id=%s&redirect_uri=%s",
                            $setPasswordLink,
                            $this->payload['summit_marketing_site_oauth2_client_id'],
                            urlencode($this->payload['summit_marketing_site_url'])
                        );
                    }
                }
                catch (\Exception $ex){
                    Log::warning($ex);
                }
            }
        }
        return parent::handle($api);
    }

}