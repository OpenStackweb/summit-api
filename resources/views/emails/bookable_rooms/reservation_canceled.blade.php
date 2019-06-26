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
    Your Reservation for room {!! $reservation->getRoom()->getCompleteName() !!} got canceled because you did not take any action to pay it.
</p>
<p>
    Id  {!! $reservation->getId()!!}
    Owner  {!! $reservation->getOwner()->getFullName()!!}
    Email  {!! $reservation->getOwner()->getEmail()!!}
    From  {!! $reservation->getLocalStartDatetime()->format("Y-m-d H:i:s") !!}
    To    {!! $reservation->getLocalEndDatetime()->format("Y-m-d H:i:s") !!}
    Created {!! $reservation->getCreated()->format("Y-m-d H:i:s") !!}
    Amount  {!! $reservation->getAmount() !!}
    Currency {!! $reservation->getCurrency() !!}
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>