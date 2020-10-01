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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
/**
 * Class AbstractEmailJobmplements
 * @package App\Jobs\Emails
 */
abstract class AbstractEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @var string
     */
    protected $template_identifier;

    /**
     * @var string
     */
    protected $to_email;

    /**
     * @var string|null
     */
    protected $subject;

    /**
     * AbstractEmailJob constructor.
     * @param array $payload
     * @param string|null $template_identifier
     * @param string $to_email
     * @param string|null $subject
     */
    public function __construct(array $payload, ?string $template_identifier, string $to_email, ?string $subject = null)
    {
        $this->template_identifier = $template_identifier;
        Log::debug(sprintf("AbstractEmailJob::__construct template_identifier %s", $template_identifier));
        if(empty($this->template_identifier)){
            throw new \InvalidArgumentException("missing template_identifier value");
        }
        $this->payload = $payload;
        $this->to_email = $to_email;
        $this->subject = $subject;
    }

    /**
     * @param IMailApi $api
     * @return array
     * @throws \Exception
     */
    public function handle
    (
        IMailApi $api
    )
    {
        try {
            Log::debug(sprintf("AbstractEmailJob::handle template_identifier %s to_email %s", $this->template_identifier, $this->to_email));
            return $api->sendEmail($this->payload, $this->template_identifier, $this->to_email, $this->subject);
        }
        catch (\Exception $ex){
            Log::error(sprintf("AbstractEmailJob::sendEmail template_identifier %s to_email %s",  $this->template_identifier, $this->to_email));
            Log::error($ex);
            throw $ex;
        }
    }

    abstract protected function getEmailEventSlug():string;

    /**
     * @param Summit $summit
     * @return string|null
     */
    protected function getEmailTemplateIdentifierFromEmailEvent(Summit $summit):?string{
        return $summit->getEmailIdentifierPerEmailEventFlowSlug($this->getEmailEventSlug());
    }
}