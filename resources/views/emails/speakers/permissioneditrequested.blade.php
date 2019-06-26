<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
    Dear {{ $request->getSpeaker()->getFullName() }},
</p>
<p>
    User {{ $request->getRequestedBy()->getFullName() }} has requested to be able to edit your Speaker Profile.
</p>
<p>
    To Allow that please click on the following link <a href="{{$request->getConfirmationLink($request->getSpeaker()->getId(), $token)}}">Allow</a>.
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>