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
    You had requested a refund for your reservation.
</p>
<p>
    Please take note of the reservation info bellow:
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