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
use App\Services\Apis\IPasswordlessAPI;
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Class AbstractSummitAttendeeTicketEmail
 * @package App\Jobs\Emails\Registration
 */
abstract class AbstractSummitAttendeeTicketEmail extends AbstractSummitEmailJob
{

    public function handle
    (
        IMailApi $api
    )
    {
        // default values
        if(!isset($this->payload[IMailTemplatesConstants::summit_marketing_site_url_magic_link]))
            $this->payload[IMailTemplatesConstants::summit_marketing_site_url_magic_link] = '';

        if(!isset($this->payload[IMailTemplatesConstants::raw_summit_virtual_site_url]))
            $this->payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = '';

        if(!isset($this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url]))
            $this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = '';

        if(!isset($this->payload[IMailTemplatesConstants::edit_ticket_link]))
            $this->payload[IMailTemplatesConstants::edit_ticket_link] = '';

        if
        (
            isset($this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url]) &&
            !empty($this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url]) &&
            isset($this->payload[IMailTemplatesConstants::owner_email]) &&
            !empty($this->payload[IMailTemplatesConstants::owner_email])
        ){
            $this->payload[IMailTemplatesConstants::edit_ticket_link] =
                sprintf
                (
                    "%s/#login=1&email=%s&BackUrl=/a/my-tickets",
                    $this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url],
                    $this->payload[IMailTemplatesConstants::owner_email]
                );
        }

        // IOC bc we cant change the signature
        $memberService = App::make(IMemberService::class);
        $passwordlessApi = App::make(IPasswordlessAPI::class);

        if(isset($this->payload[IMailTemplatesConstants::summit_virtual_site_url])){
            $this->payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $this->payload['summit_virtual_site_url'];
        }

        if(isset($this->payload[IMailTemplatesConstants::summit_marketing_site_url])){
            $this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $this->payload['summit_marketing_site_url'];
        }

        if($memberService instanceof IMemberService){

            $email = $this->payload[IMailTemplatesConstants::owner_email];
            // check if exist at idp
            $user = $memberService->checkExternalUser($email);

            if(is_null($user)){

                // user does not exist at idp so we need to generate a registration request
                // and create the magic links to complete the registration request

                try {

                    $userRegistrationRequest = $memberService->emitRegistrationRequest
                    (
                        $email,
                        $this->payload[IMailTemplatesConstants::owner_first_name] ?? '',
                        $this->payload[IMailTemplatesConstants::owner_last_name] ?? '',
                        $this->payload[IMailTemplatesConstants::owner_company] ?? ''
                    );

                    $setPasswordLink = $userRegistrationRequest['set_password_link'];

                    if (isset($this->payload[IMailTemplatesConstants::summit_virtual_site_url]) &&
                        !empty($this->payload[IMailTemplatesConstants::summit_virtual_site_url]) &&
                        isset($this->payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id]) &&
                        !empty($this->payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id])) {
                        $this->payload[IMailTemplatesConstants::summit_virtual_site_url] = sprintf(
                            "%s?client_id=%s&redirect_uri=%s",
                            $setPasswordLink,
                            $this->payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id],
                            urlencode($this->payload[IMailTemplatesConstants::summit_virtual_site_url])
                        );
                    }

                    if (isset($this->payload[IMailTemplatesConstants::summit_marketing_site_url]) &&
                        !empty($this->payload[IMailTemplatesConstants::summit_marketing_site_url]) &&
                        isset($this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id]) &&
                        !empty($this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id])) {
                        $this->payload[IMailTemplatesConstants::summit_marketing_site_url] = sprintf(
                            "%s?client_id=%s&redirect_uri=%s",
                            $setPasswordLink,
                            $this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id],
                            urlencode($this->payload[IMailTemplatesConstants::summit_marketing_site_url])
                        );
                    }
                }
                catch (\Exception $ex){
                    Log::warning($ex);
                }
            }
        }

        if($passwordlessApi instanceof IPasswordlessAPI &&
            isset($this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url]) &&
            !empty($this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url]) &&
            isset($this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id]) &&
            !empty($this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id] &&
            isset($this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]) &&
            !empty($this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]))){

            $otp = null;
            Log::debug
            (
                sprintf
                (
                    "AbstractSummitAttendeeTicketEmail::handle trying to get OTP for email %s",
                    $this->to_email
                )
            );

            try {
                // generate inline OTP
                $otp = $passwordlessApi->generateInlineOTP
                (
                    $this->to_email,
                    $this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id],
                    $this->payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]
                );
            } catch (\Exception $ex) {
                Log::error($ex);
                $otp = null;
            }

            if (!is_null($otp)) {
                Log::debug
                (
                    sprintf
                    (
                        "AbstractSummitAttendeeTicketEmail::handle got for email %s otp %s",
                        $this->to_email,
                        json_encode($otp)
                    )
                );

                $url_parts  = parse_url($this->payload[IMailTemplatesConstants::raw_summit_marketing_site_url]);

                if($url_parts && isset($url_parts['scheme']) && isset($url_parts['host']))
                $domain = sprintf("%s://%s", $url_parts["scheme"], $url_parts["host"]);
                // must be registered as valid redirect url under the oauth2 client
                $back_url = sprintf("%s/a/extra-questions", $domain);
                $this->payload[IMailTemplatesConstants::summit_marketing_site_url_magic_link] = sprintf
                (
                    "%s/auth/login?login_hint=%s&otp_login_hint=%s&backUrl=%s"
                    , $domain
                    , urlencode($this->to_email)
                    , urlencode($otp['value'])
                    , urlencode($back_url)
                );

                Log::debug
                (
                    sprintf
                    (
                        "AbstractSummitAttendeeTicketEmail::handle got summit_marketing_site_url_magic_link %s",
                        $this->payload[IMailTemplatesConstants::summit_marketing_site_url_magic_link]
                    )
                );
            }
        }

        return parent::handle($api);
    }

    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::summit_marketing_site_url_magic_link]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::edit_ticket_link]['type'] = 'string';

        return $payload;
    }

}