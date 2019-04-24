<p>
    Dear {{ $request->getSpeaker()->getFullName() }},
    User {{ $request->getRequestedBy()->getFullName() }} has requested to be able to edit your Speaker Profile.
    To Allow that please click on the following link <a href="{{$request->getConfirmationLink($request->getSpeaker()->getId(), $token)}}">Allow</a>.
</p>
<p>
    The OpenStack Team.
</p>