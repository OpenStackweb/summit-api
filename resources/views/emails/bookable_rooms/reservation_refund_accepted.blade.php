<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
    Dear {!! $owner_fullname!!}
</p>
<p>
    Your Refund Request Reservation for room {!! $room_complete_name !!} is confirmed.
</p>
<p>
    Please take note of the reservation info below:
</p>
<p>
    <ul>
    <li>{!! $room_complete_name !!}</li>
    <li>From {!! $reservation_start_datetime !!}</li>
    <li>To {!! $reservation_end_datetime !!}</li>
    <li>Room Capacity {!! $room_capacity !!}</li>
    <li>Amount {!! $reservation_currency !!} {!! $reservation_refunded_amount !!}</li>
    </ul>
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>
