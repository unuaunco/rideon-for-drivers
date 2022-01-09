    var map;
    var ajaxMarker = [];
    var googleMarker = [];
    var mapIcons = {
        Active: APP_URL + '/images/marker_green.png',
        Online: APP_URL + '/images/marker_dgreen.png',
        Trip: APP_URL + '/images/marker_dgreen.png',
        Offline: APP_URL + '/images/marker_pink.png',
        Inactive: APP_URL + '/images/marker_pink_plus.png',
    }


    function initMap() {
        var mapCanvas = document.getElementById('map');

        if (!mapCanvas) {
            return false;
        }
        var mapOptions = {
            zoom: 4,
            minZoom: 1,
            zoomControl: true,
            center: { lat: -24.9917068, lng: 115.220367 },
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false
        }
        map = new google.maps.Map(mapCanvas, mapOptions);

        // Create the search box and link it to the UI element.
        const input = document.getElementById("pac-input");
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
        // Bias the SearchBox results towards current map's viewport.
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });
        let markers = [];
        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();

            if (places.length == 0) {
                return;
            }
            // Clear out the old markers.
            markers.forEach(marker => {
                marker.setMap(null);
            });
            markers = [];
            // For each place, get the icon, name and location.
            const bounds = new google.maps.LatLngBounds();
            places.forEach(place => {
                if (!place.geometry) {
                    console.log("Returned place contains no geometry");
                    return;
                }
                const icon = {
                    url: place.icon,
                    size: new google.maps.Size(71, 71),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(17, 34),
                    scaledSize: new google.maps.Size(25, 25)
                };
                // Create a marker for each place.
                markers.push(
                    new google.maps.Marker({
                        map,
                        icon,
                        title: place.name,
                        position: place.geometry.location
                    })
                );

                if (place.geometry.viewport) {
                    // Only geocodes have viewport.
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });
            map.fitBounds(bounds);
        });


        setInterval(ajaxMapData, 100000);
        ajaxMapData();

    }

    function ajaxMapData() {
        clearOverlays();
        $.ajax({
            url: COMPANY_ADMIN_URL + '/mapdata',
            dataType: "JSON",
            type: "GET",
            success: function(data) {
                ajaxMarker = data;
                if (ajaxMarker.length != 0) {
                    angular.forEach(ajaxMarker, function(value, key) {
                        var icon_img = value.status;
                        if (value.status != 'Inactive') {
                            if (value.user_type == 'Driver') {
                                if (value.status == 'Active' && value.driver_location != null) {
                                    var icon_img = null;
                                    if (value.driver_location_status) { icon_img = value.driver_location_status; } else { icon_img = 'Offline'; }
                                } else if (value.status != 'Active') {
                                    var icon_img = 'Inactive';
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
                                    map: map,
                                    title: value.first_name + " " + value.last_name,
                                    icon: icon,
                                });

                                googleMarker.push(marker);
                                google.maps.event.addListener(marker, 'click', function() {
                                    if (!value.profile_picture) { var profile_pic = "https://rideon-cdn.sgp1.cdn.digitaloceanspaces.com/images/users/user.jpeg" } else { profile_pic = value.profile_picture }
                                    var html = '<span class="close_user_details"><i class="fa fa-times"></i></span>';
                                    html += '<div class="user_background col-md-3">';
                                    html += '<img src="' + profile_pic + '" class="img-circle"></div>';
                                    html += '<div class="user_details col-md-9">';
                                    html += '<h3 class="text-capitalize">' + value.first_name + " " + value.last_name + ' (' + value.user_type + ')</h3> ';
                                    // if (LOGIN_USER_TYPE == 'admin') {
                                    //     html += '<p class="text-capitalize">' + value.company_name + '</p> ';
                                    // }
                                    html += '<p title="' + value.email + '"><i class="fa fa-envelope" aria-hidden="true"></i> : <span class="sety">' + value.email + '</span></p>';
                                    html += '<p title="' + value.hidden_mobile_number + '"><i class="fa fa-phone" aria-hidden="true"></i> : <span class="sety">' + value.hidden_mobile_number + '</span></p>';
                                    html += '<br><p title="' + value.updated_at + '" style="color:grey;">Last location update at : <span class="sety" style="color:grey;">' + value.updated_at + '</span></p>';
                                    html += '</div>';
                                    $('#user_details').show();

                                    $('#user_details').html(html);

                                });
                            }
                        }
                    });
                }
            }
        });
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