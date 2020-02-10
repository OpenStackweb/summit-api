<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Thank you for your RSVP to <strong>{!! $event_title !!}</strong> at {!! $event_date !!} . For your convenience, we have added this to My Schedule within the Summit Management tool.</p>
@if(!empty($event_uri))
<p>Be sure to synch it to your calendar by going <a href="{!! $event_uri !!}" target="_blank">here</a>.</p>
@endif
Please present a printed copy of this email at the entrance where the event is being held.<br/><br/>

******************************************************************************************<br/>
<p>
    Attendee: {!! $owner_fullname !!}<br/>
    Event: {!! $event_title !!}<br/>
    Confirmation #: {!! $confirmation_number !!}<br/>
</p>
******************************************************************************************<br/>

<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>
