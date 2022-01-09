<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Tracking</title>

    <!-- Here assets start-->
    <script type="text/javascript" charset="UTF-8" src="https://js.api.here.com/v3/3.1/mapsjs-core.js"></script>
    <script type="text/javascript" charset="UTF-8" src="https://js.api.here.com/v3/3.1/mapsjs-service.js"></script>
    <script type="text/javascript" charset="UTF-8" src="https://js.api.here.com/v3/3.1/mapsjs-mapevents.js"></script>
    <script type="text/javascript" charset="UTF-8" src="https://js.api.here.com/v3/3.1/mapsjs-ui.js"></script>
    <script type="text/javascript" charset="UTF-8" src="https://js.api.here.com/v3/3.1/mapsjs-clustering.js"></script>
    <script type="text/javascript" charset="UTF-8" src="https://js.api.here.com/v3/3.1/mapsjs-data.js"></script>
    <link rel="stylesheet" type="text/css" href="https://js.api.here.com/v3/3.1/mapsjs-ui.css" />
    <!-- Here assets end-->
</head>
<body>
    <input type="hidden" id="tracking-id" name="trackingId" value="{{$trackingId}}">
    <div id="tracking-app">
        <tracking-app/>
    </div>
    <script src="{{url('js/tracking.js')}}"></script>
</body>
</html>