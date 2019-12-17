<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
    Dear {!! $owner_fullname !!}
</p>
<p>
    Your Reservation for room {!! $room_complete_name !!} has been created successfully.
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>
