<?php namespace App\Mail;
/**
 * Copyright 2015 OpenStack Foundation
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
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
/**
 * Class MUXExportExcerptMail
 * @package App\Mail
 */
class MUXExportExcerptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tries = 1;

    private $mail_to;

    /**
     * @var string
     */
    private $step;

    /**
     * @var string
     */
    private $excerpt;

    /**
     * MUXExportExcerptMail constructor.
     * @param $mail_to
     * @param string $step
     * @param string $excerpt
     */
    public function __construct($mail_to, string $step, string $excerpt)
    {
        $this->mail_to = $mail_to;
        $this->step = $step;
        $this->excerpt = $excerpt;
    }

    public function build()
    {
        $subject = sprintf("[%s] Mux Export Process - %s", Config::get('app.tenant_name'), $this->step);
        Log::warning(sprintf("MUXExportExcerptMail::build to %s", $this->mail_to));
        return $this->from(Config::get("mail.from"))
            ->to($this->mail_to)
            ->subject($subject)
            ->view('emails.mux_export_excerpt');
    }
}
