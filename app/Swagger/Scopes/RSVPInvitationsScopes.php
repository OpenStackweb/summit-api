<?php

namespace App\Security;

class RSVPInvitationsScopes
{
    public const string Read      = 'rsvp-invitations/read';  // read all elections
    public const string  Write    = 'rsvp-invitations/write';
    public const string  Send     = 'rsvp-invitations/send';
}