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
    Your Refund Request Reservation for room {!! $reservation->getRoom()->getCompleteName() !!} has been confirmed.
</p>
<p>
    Please take note of the reservation info bellow:
</p>
<p>
    From  {!! $reservation->getLocalStartDatetime()->format("Y-m-d H:i:s") !!}
    To    {!! $reservation->getLocalEndDatetime()->format("Y-m-d H:i:s") !!}
    Room Capacity {!! $reservation->getRoom()->getCapacity() !!}
    Amount  {!! $reservation->getRefundedAmount() !!}
    Currency {!! $reservation->getCurrency() !!}
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>