<?php namespace App\Jobs\Emails\Schedule\RSVP;

use App\Jobs\Emails\AbstractExcerptEmailJob;

class RSVPConfirmationExcerptEmail extends AbstractExcerptEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_RSVP_CONFIRMATION_EXCERPT';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_RSVP_CONFIRMATION_EXCERPT';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_RSVP_CONFIRMATION_EXCERPT';
}