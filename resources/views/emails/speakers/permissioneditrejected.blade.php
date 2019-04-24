<p>
    Dear {{ $request->getRequestedBy()->getFullName() }},
    User {{ $request->getSpeaker()->getFullName() }} has rejected your request to edit his/her Speaker Profile.
</p>
<p>
    The OpenStack Team.
</p>