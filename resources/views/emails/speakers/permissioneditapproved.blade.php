<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
    Dear {{ $request->getRequestedBy()->getFullName() }},
</p>
<p>
    User {{ $request->getSpeaker()->getFullName() }} has approved your request to edit his/her Speaker Profile.
</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>