app.controller('tracking', ['$scope', '$http', '$compile', '$filter', function($scope, $http, $compile, $filter) {
    $scope.Driverfilter = function(driver) {
        return function(item) {
            if (typeof item === 'undefined' || typeof driver === 'undefined') {
                return true;
            }
            return ((item.first_name).toLowerCase()).includes(driver.toLowerCase()) || (item.mobile_number).includes(driver);
        };
    };

    var autocomplete;
    $scope.from_marker;
    $scope.to_marker;
    $scope.vehicle_detail_km = 0
    $scope.vehicle_detail_minutes = 0
    $scope.vehicle_detail_km_fare = 0
    $scope.vehicle_detail_min_fare = 0
    $scope.vehicle_detail_total_fare = 0
    $scope.vehicle_detail_minimum_fare = 0
    $scope.vehicle_detail_base_fare = 0
    $scope.vehicle_detail_peak_price = 0
    $scope.ignore_assigned = []
    $scope.mapRadius = 12
    $scope.from_pin = APP_URL + '/images/PinFrom.png'
    $scope.to_pin = APP_URL + '/images/PinTo.png'
    $scope.car_pin = APP_URL + '/images/car_black.png'
    $scope.vehicle_detail_peak_fare = 0
        //initAutocomplete();
    initMap();

    var autoCompleteOptions = {
        fields: ['place_id', 'name', 'types', 'formatted_address', 'address_components', 'geometry', 'utc_offset']
    };

    //Auto complete to pickup & drop location
    function initAutocomplete() {
        pick_up_location_autocomplete = new google.maps.places.Autocomplete(document.getElementById('input_pick_up_location'), autoCompleteOptions);
        pick_up_location_autocomplete.addListener('place_changed', pick_up_location_address);

        drop_off_location_autocomplete = new google.maps.places.Autocomplete(document.getElementById('input_drop_off_location'), autoCompleteOptions);
        drop_off_location_autocomplete.addListener('place_changed', drop_off_location_Address);

    }

    function pick_up_location_address() {
        pickup_place = pick_up_location_autocomplete.getPlace();
        $('#input_pick_up_location').val(pickup_place.formatted_address);
        $scope.utc_offset = pickup_place.utc_offset;

        $scope.pick_up_latitude = pickup_place.geometry.location.lat();
        $scope.pick_up_longitude = pickup_place.geometry.location.lng();
        $('#pick_up_latitude').val($scope.pick_up_latitude)
        $('#pick_up_longitude').val($scope.pick_up_longitude)
        $('#utc_offset').val($scope.utc_offset)
        if (typeof $scope.from_marker !== 'undefined') {
            $scope.from_marker.setMap(null);
        }

        $scope.from_marker = new google.maps.Marker({
            map: $scope.map,
            draggable: true,
            icon: $scope.from_pin,
            animation: google.maps.Animation.DROP,
            position: { lat: $scope.pick_up_latitude, lng: $scope.pick_up_longitude }
        });
        $scope.map.setZoom($scope.mapRadius);
        $scope.map.panTo($scope.from_marker.position);
        $scope.from_marker.addListener('dragend', fromMarkerDrag);
        calculateAndDisplayRoute();
        if (typeof $scope.pick_up_latitude === "undefined" || typeof $scope.pick_up_longitude === "undefined") {
            $('#input_map_zoom').attr("disabled", true);
        } else {
            $('#input_map_zoom').attr("disabled", false);
        }
    }

    function drop_off_location_Address() {
        drop_place = drop_off_location_autocomplete.getPlace();
        $('#input_drop_off_location').val(drop_place.formatted_address);
        $scope.drop_off_latitude = drop_place.geometry.location.lat();
        $scope.drop_off_longitude = drop_place.geometry.location.lng();
        $('#drop_off_latitude').val($scope.drop_off_latitude)
        $('#drop_off_longitude').val($scope.drop_off_longitude)
        if (typeof $scope.to_marker !== 'undefined') {
            $scope.to_marker.setMap(null);
        }
        $scope.to_marker = new google.maps.Marker({
            map: $scope.map,
            draggable: true,
            icon: $scope.to_pin,
            animation: google.maps.Animation.DROP,
            position: { lat: $scope.drop_off_latitude, lng: $scope.drop_off_longitude }
        });
        $scope.to_marker.addListener('dragend', toMarkerDrag);
        $scope.map.setZoom($scope.mapRadius);
        $scope.map.panTo($scope.to_marker.position);
        calculateAndDisplayRoute();
    }


    $('#input_pick_up_location,#input_drop_off_location').change(function() {
        if ($('#input_pick_up_location').val() == '' || $('#input_drop_off_location').val() == '') {
            $('#input_date_time').attr('disabled', true)
        } else {
            $('#input_date_time').attr('disabled', false)
        }
    })

    //init map
    function initMap() {
        $scope.directionsService = new google.maps.DirectionsService;
        $scope.directionsDisplay = new google.maps.DirectionsRenderer({ preserveViewport: true });
        $scope.geocoder = new google.maps.Geocoder;
        var mapCanvas = document.getElementById('map');
        if (!mapCanvas) {
            return false;
        }
        var mapOptions = {
            zoomControl: false,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            center: { lat: 0, lng: 0 },
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }
        $scope.map = new google.maps.Map(mapCanvas, mapOptions);

        $scope.directionsDisplay.setMap($scope.map);

        if ($('#pick_up_latitude').val() != '' && $('#pick_up_longitude').val() != '' && $('#drop_off_latitude').val() != '' && $('#drop_off_longitude').val() != '') {
            $scope.pick_up_latitude = parseFloat($('#pick_up_latitude').val())
            $scope.pick_up_longitude = parseFloat($('#pick_up_longitude').val())
            $scope.drop_off_latitude = parseFloat($('#drop_off_latitude').val())
            $scope.drop_off_longitude = parseFloat($('#drop_off_longitude').val())
            $scope.driver_latitude = parseFloat($('#driver_latitude').val())
            $scope.driver_longitude = parseFloat($('#driver_longitude').val())
            $scope.from_marker = new google.maps.Marker({
                map: $scope.map,
                draggable: false,
                icon: $scope.from_pin,
                animation: google.maps.Animation.DROP,
                position: { lat: $scope.pick_up_latitude, lng: $scope.pick_up_longitude }
            });
            $scope.driver_marker = new google.maps.Marker({
                map: $scope.map,
                draggable: true,
                icon: $scope.car_pin,
                animation: google.maps.Animation.DROP,
                position: { lat: $scope.driver_latitude, lng: $scope.driver_longitude }
            });
            $scope.to_marker = new google.maps.Marker({
                map: $scope.map,
                icon: $scope.to_pin,
                draggable: false,
                animation: google.maps.Animation.DROP,
                position: { lat: $scope.drop_off_latitude, lng: $scope.drop_off_longitude }
            });
            $scope.map.setZoom($scope.mapRadius);
            $scope.map.panTo($scope.driver_marker.position);
            // $scope.from_marker.addListener('dragend', fromMarkerDrag);
            // $scope.to_marker.addListener('dragend', toMarkerDrag);
            calculateAndDisplayRoute();
        }
    }

    //Show route on map
    function calculateAndDisplayRoute() {
        if (typeof $scope.pick_up_latitude === "undefined" || typeof $scope.pick_up_longitude === "undefined" || typeof $scope.drop_off_latitude === "undefined" || typeof $scope.drop_off_longitude === "undefined") {
            return false;
        }
        direction = $('#direction').val()
        if (direction === 'En route to pick-up') {
            $scope.destination_latitude = $scope.pick_up_latitude;
            $scope.destination_longitude = $scope.pick_up_longitude;
        } else if (direction === 'En route to drop-off') {
            $scope.destination_latitude = $scope.drop_off_latitude;
            $scope.destination_longitude = $scope.drop_off_longitude;
        } else {
            $scope.destination_latitude = $scope.driver_latitude;
            $scope.destination_longitude = $scope.driver_longitude;
        }
        //  
        $scope.directionsService.route({
            origin: {
                lat: parseFloat($scope.driver_latitude),
                lng: parseFloat($scope.driver_longitude)
            },
            destination: {
                lat: parseFloat($scope.destination_latitude),
                lng: parseFloat($scope.destination_longitude)
            },
            travelMode: 'DRIVING'
        }, function(response, status) {
            if (status === 'OK') {
                $scope.directionsDisplay.setDirections(response);
                $scope.directionsDisplay.setOptions({ suppressMarkers: true });
            } else {
                console.log(status);
                // if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                // 	calculateAndDisplayRoute();
                // 	return;
                // }

                // window.alert('Directions request failed due to ' + status);
            }
        });
    }

    function fromMarkerDrag(evt) {
        $scope.pick_up_latitude = evt.latLng.lat()
        $scope.pick_up_longitude = evt.latLng.lng()
        $('#pick_up_latitude').val($scope.pick_up_latitude)
        $('#pick_up_longitude').val($scope.pick_up_longitude)
        var latlng = { lat: $scope.pick_up_latitude, lng: $scope.pick_up_longitude };
        calculateAndDisplayRoute();
        getLocation(latlng, 'input_pick_up_location')
            // $scope.utc_offset  = pickup_place.utc_offset;
            // $('#utc_offset').val($scope.utc_offset)
    }

    function toMarkerDrag(evt) {
        $scope.drop_off_latitude = evt.latLng.lat()
        $scope.drop_off_longitude = evt.latLng.lng()
        $('#drop_off_latitude').val($scope.drop_off_latitude)
        $('#drop_off_longitude').val($scope.drop_off_longitude)
        var latlng = { lat: $scope.drop_off_latitude, lng: $scope.drop_off_longitude };
        calculateAndDisplayRoute();
        getLocation(latlng, 'input_drop_off_location')
    }

    //find location from latlang
    function getLocation(latlng, field) {
        $scope.geocoder.geocode({ 'location': latlng }, function(results, status) {
            if (status === 'OK') {
                if (results[0]) {
                    $('#' + field).val(results[0].formatted_address);
                } else {
                    window.alert('No results found');
                }
            } else {
                window.alert('Please choose the valid location');
            }
        });
    }

    //Map zoom by filter
    $scope.map_zoom = function(radius) {
        if (radius == 0) {
            $scope.mapRadius = 13
            if ($scope.pick_up_latitude != '' && $scope.pick_up_longitude != '') {
                $scope.map.setZoom($scope.mapRadius);
            }
        } else {
            var newRadius = Math.round(24 - Math.log(radius) / Math.LN2);
            $scope.mapRadius = newRadius - 9;
            if ($scope.pick_up_latitude != '' && $scope.pick_up_longitude != '') {
                var pt = new google.maps.LatLng($scope.pick_up_latitude, $scope.pick_up_longitude);
                $scope.map.setCenter(pt);
                $scope.map.setZoom($scope.mapRadius);
            }
        }
    }


    //datetime picker
    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }
    $('.reset').click(function(e) {
        e.preventDefault();
        location.reload();
    });

    var mapIcons = {
        Online: APP_URL + '/images/car_green.png',
        Scheduled: APP_URL + '/images/car_red.png',
        Begin_trip: APP_URL + '/images/car_blue.png',
        End_trip: APP_URL + '/images/car_yellow.png',
        Offline: APP_URL + '/images/car_black.png',
    }
    var googleMarker = [];
    var map;
    var infoWindow = new google.maps.InfoWindow();

    //Show drivers on map
    $scope.MapData = function() {
        clearOverlays();
        if ($scope.vehicle_types.length != 0) {
            drivers = $scope.drivers
            angular.forEach(drivers, function(value, key) {
                if (value.driver_current_status == $scope.driver_availability || $scope.driver_availability == '') {
                    var icon_img = value.driver_current_status;
                    if (icon_img == 'Begin trip') {
                        icon_img = 'Begin_trip'
                    } else if (icon_img == 'End trip') {
                        icon_img = 'End_trip'
                    }
                    var icon = {
                        url: mapIcons[icon_img], // url
                        scaledSize: new google.maps.Size(23, 30), // scaled size
                        origin: new google.maps.Point(0, 0), // origin
                        anchor: new google.maps.Point(0, 0) // anchor
                    };

                    marker = new google.maps.Marker({
                        position: {
                            lat: parseFloat(value.latitude),
                            lng: parseFloat(value.longitude)
                        },
                        id: value.id,
                        map: $scope.map,
                        title: value.first_name + " " + value.last_name,
                        icon: icon,
                    });
                    googleMarker.push(marker);
                    google.maps.event.addListener(marker, 'click', function() {
                        var html = '';
                        html += '<div class="user_background col-md-3">';
                        html += '<img src="' + value.src + '" class="img-circle" width="100%" height="auto"></div>';
                        html += '<div class="user_details col-md-9">';
                        html += '<h3 class="text-capitalize">' + value.first_name + '</h3> ';
                        html += '<p title="' + value.email + '"><i class="fa fa-envelope" aria-hidden="true"></i> : <span class="sety">' + value.email + '</span></p>';
                        html += '<p title="' + value.hidden_mobile_number + '"><i class="fa fa-phone" aria-hidden="true"></i> : <span class="sety">' + value.hidden_mobile_number + '</span></p>';
                        html += '</div>';
                        infoWindow.setContent(html)
                        infoWindow.open(map, marker);
                        /*$('#user_details').show();
                        $('#user_details').html(html);*/
                    });
                }
            });
        }
    }

    function clearOverlays() {
        for (var i = 0; i < googleMarker.length; i++) {
            googleMarker[i].setMap(null);
        }
        googleMarker.length = 0;
    }
    $(document).on("click", ".close_user_details", function() {
        $('#user_details').hide();
    })

    $scope.page_loading = 1;
}]);