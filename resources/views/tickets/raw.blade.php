<h2>{!! $summit_name !!}</h2>
<h3>{!! $ticket_type !!} ( {!! $price !!} )</h3>
<p>{!!$location_name  !!}</p>
<p>{!! $dates !!}</p>
<p><b>Order information</b></p>
<p>Order # {!! $order_number !!}</p>
<p>Ordered by {!! $owner_full_name !!} on {!! $order_creation_date !!}</p>
@if(!empty($attendee_name))
<p><b>Attendee</b></p>
<p>{!! $attendee_name!!}</p>
@endif