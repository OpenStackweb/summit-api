<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
    There is a new reservation refund request available to process
</p>
<p>
    Please take note of the reservation info below:
</p>
<p>
    <ul>
    <li>Id {!! $reservation_id!!}</li>
    <li>{!! $room_complete_name !!}</li>
    <li>Owner {!! $owner_fullname!!}</li>
    <li>Email {!! $owner_email !!}</li>
    <li>From {!! $reservation_start_datetime !!}</li>
    <li>To {!! $reservation_end_datetime !!}</li>
    <li>Created {!! $reservation_created_datetime !!}</li>
    <li>Amount {!! $reservation_currency !!} {!! $reservation_amount !!}</li>
</ul>
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>
