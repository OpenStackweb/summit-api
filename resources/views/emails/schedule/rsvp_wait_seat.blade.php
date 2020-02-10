<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Thank you for signing up to <strong>{!! $event_title !!}<</strong> at {!! $event_date !!}  At the moment, this class is full.
    However, you've been added to the waitlist.  If space becomes available, the Workshop presenter will contact you to let you know.</p>

<p>For your convenience, we have added this to My Schedule within the Summit Management tool.</p>
Be sure to synch it to your calendar by going <a href="{!! $event_uri !!}" target="_blank">here</a>.

If you are removed from the waitlist, please present a printed copy of this email at the entrance where the event is being held.<br/><br/>

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
