<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
    Dear {!! $reservation->getOwner()->getFullName() !!},
</p>
<p>
    Your Reservation for room {!! $reservation->getRoom()->getCompleteName() !!} has been created successfully.
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>