// Helper functions
Date.diffFromNow = function(date, datepart = 'm') {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    datepart = datepart.toLowerCase();
    let diff = Math.abs((new Date()) - date);
    var divideBy = {
        w: 604800000,
        d: 86400000,
        h: 3600000,
        m: 60000,
        s: 1000
    };

    return Math.floor(diff / divideBy[datepart]);
}


Date.dateDiff = function(datepart, fromdate, todate) {
    datepart = datepart.toLowerCase();
    var diff = Math.abs(todate - fromdate);
    var divideBy = {
        w: 604800000,
        d: 86400000,
        h: 3600000,
        m: 60000,
        s: 1000
    };

    return Math.floor(diff / divideBy[datepart]);
}

$(function() {

    //Fill country code

    $('#country_code_view').val('+' + $('#input_country_code').val());

    $('#input_country_code').on('change', function() {
        $('#country_code_view').val('+' + $('#input_country_code').val())
    });

    //Handle datetime picker
    jQuery.datetimepicker.setLocale('en');

    $('#input_date_time').datetimepicker({
        format: 'Y-m-d H:i'
    });

    // FORM dynamic
    let prevText = '\u276E';
    let nextText = '\u276F';
    var currentStep = 0;

    $('#previous-button').prop('disabled', true);

    $('#slider-0').show();

    $('#step-note-0').show();

    function changeStep(newStep){
        $('#slider-' + currentStep).hide();
        $('#step-note-' + currentStep).hide();
        currentStep = newStep;
        $('.dot-move').css('left', currentStep / 4 * 100 + '%');
        $('#slider-' + currentStep).show();
        $('#step-note-' + currentStep).show();
        if (currentStep === 4) {
            $('#next-button').text("Submit");
            $('#customer_phone_number').val($("#country_code_view").val() + $("#input_mobile_number").val());
            $('#customer_name').val($("#input_first_name").val() + ' ' + $("#input_last_name").val());
            $('#badge-pickup').text($("#input_pick_up_location").val());
            $('#badge-drop').text($("#input_drop_off_location").val());
            $('#badge-time').text($("#input_date_time").val() + "(" + moment.tz.guess() + ")");
            $('#badge-cname').text($('#customer_name').val());
            $('#badge-cphone').text($("#country_code_view").val() + $("#input_mobile_number").val());
            $('#badge-notes').text($("#input_order_description").val());
        } else {
            $('#next-button').text(nextText);
        }
    }

    if($('.error_drop_off_location').text() !== ''){
        changeStep(0);
    }
    else if($('.error_pick_up_location').text() !== ''){
        changeStep(0);
    }
    else if($('.error_estimate_time').text() !== ''){
        changeStep(1);
    }
    else if($('.error_mobile_number').text() !== ''){
        changeStep(2);
    }
    else if($('.error_first_name').text() !== ''){
        changeStep(2);
    }
    else if($('.error_last_name').text() !== ''){
        changeStep(2);
    }

    $('.dot').on('click', function(e) {
        let stepToGo = Number($(this).attr('id').replace('step-dot-', ''));
        changeStep(stepToGo);
    });

    $('#next-button').on('click', function(e) {

        if (currentStep < 5) {

            if (currentStep === 4) {
                $(this).text("Saving...");
                $('#customer_phone_number').val($("#country_code_view").val() + $("#input_mobile_number").val());
                $('#customer_name').val($("#input_first_name").val() + ' ' + $("#input_last_name").val());
                $("#delivery_order").submit();
            } else {
                $(this).text("Saving...");

                $('#slider-' + currentStep).hide();
                $('#step-note-' + currentStep).hide();

                currentStep += 1;
                $('.dot-move').css('left', currentStep / 4 * 100 + '%');
                $('#slider-' + currentStep).show();
                $('#step-note-' + currentStep).show();
                if (currentStep === 4) {
                    $('#next-button').text("Submit");
                    $('#customer_phone_number').val($("#country_code_view").val() + $("#input_mobile_number").val());
                    $('#customer_name').val($("#input_first_name").val() + ' ' + $("#input_last_name").val());
                    $('#badge-pickup').text($("#input_pick_up_location").val());
                    $('#badge-drop').text($("#input_drop_off_location").val());
                    $('#badge-time').text($("#input_date_time").val() + "(" + moment.tz.guess() + ")");
                    $('#badge-cname').text($('#customer_name').val());
                    $('#badge-cphone').text($("#country_code_view").val() + $("#input_mobile_number").val());
                    $('#badge-notes').text($("#input_order_description").val());
                } else {
                    $(this).text(nextText);
                }

                console.log(currentStep);

                e.preventDefault();

            }

        }
    });

    $('#previous-button').on('click', function(e) {
        if (currentStep > 0) {
            if (currentStep === 4) {
                $('#next-button').text(nextText);
            }
            $(this).text("Previous...");
            $('#slider-' + currentStep).hide();
            $('#step-note-' + currentStep).hide();
            currentStep -= 1;
            $('.dot-move').css('left', currentStep / 4 * 100 + '%');
            $('#slider-' + currentStep).show();
            $('#step-note-' + currentStep).show();
            $(this).text(prevText);
            console.log(currentStep);
            e.preventDefault();
        }
    });

    $('#input_date_time').on('change', function(e) {
        $('#input_estimate_time').val(Date.diffFromNow($('#input_date_time').val()));
        // console.log($('#input_estimate_time').val());
    });
});

app.controller('delivery_order', ['$scope', '$http', '$compile', '$filter', function($scope, $http, $compile, $filter) {

    var autocomplete;
    $scope.from_marker;
    $scope.to_marker;
    $scope.car_marker;
    $scope.vehicle_detail_km = 0
    $scope.vehicle_detail_minutes = 0
    $scope.vehicle_detail_km_fare = 0
    $scope.vehicle_detail_min_fare = 0
    $scope.vehicle_detail_total_fare = 0
    $scope.vehicle_detail_minimum_fare = 0
    $scope.vehicle_detail_base_fare = 0
    $scope.vehicle_detail_peak_price = 0
    $scope.ignore_assigned = []
    $scope.mapRadius = 13
    $scope.from_pin = APP_URL + '/images/PinFrom.png'
    $scope.to_pin = APP_URL + '/images/PinTo.png'
    $scope.car_pin = APP_URL + '/images/car_black.png'
    $scope.vehicle_detail_peak_fare = 0
    $scope.pick_up_location_autocomplete;
    $scope.drop_off_location_autocomplete;
    initAutocomplete();
    initMap();


    // Handle pick up address automatically
    if ($('#input_pick_up_location').val() !== "") {
        let placeService = new google.maps.places.PlacesService($scope.map);

        const request = {
            query: $('#input_pick_up_location').val(),
            fields: ["geometry", "formatted_address"],
        };

        placeService.findPlaceFromQuery(request, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                var pickup_place = results[0];
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
        });

    }

    var autoCompleteOptions = {
        fields: ['place_id', 'name', 'types', 'formatted_address', 'address_components', 'geometry', 'utc_offset']
    };

    //Auto complete to pickup & drop location
    function initAutocomplete() {
        $scope.pick_up_location_autocomplete = new google.maps.places.Autocomplete(document.getElementById('input_pick_up_location'), autoCompleteOptions);
        $scope.pick_up_location_autocomplete.addListener('place_changed', getPickUpAddress);

        $scope.drop_off_location_autocomplete = new google.maps.places.Autocomplete(document.getElementById('input_drop_off_location'), autoCompleteOptions);
        $scope.drop_off_location_autocomplete.addListener('place_changed', getDropOffAddress);

    }

    function getPickUpAddress() {
        var pickup_place = $scope.pick_up_location_autocomplete.getPlace();
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

    function getDropOffAddress() {
        let drop_place = $scope.drop_off_location_autocomplete.getPlace();
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

    //Map initialization
    function initMap() {
        $scope.directionsService = new google.maps.DirectionsService;
        $scope.directionsDisplay = new google.maps.DirectionsRenderer({ preserveViewport: true });
        $scope.geocoder = new google.maps.Geocoder;
        var mapCanvas = document.getElementById('map');
        if (!mapCanvas) {
            return false;
        }
        var mapOptions = {
            zoom: 5,
            minZoom: 1,
            zoomControl: true,
            center: { lat: -30.681414, lng: 141.176949 },
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }
        $scope.map = new google.maps.Map(mapCanvas, mapOptions);

        $scope.directionsDisplay.setMap($scope.map);
        $scope.assigned_driver = $('#driver_id').val();
        if ($('#pick_up_latitude').val() != '' && $('#pick_up_longitude').val() != '' && $('#drop_off_latitude').val() != '' && $('#drop_off_longitude').val() != '') {
            $scope.pick_up_latitude = parseFloat($('#pick_up_latitude').val())
            $scope.pick_up_longitude = parseFloat($('#pick_up_longitude').val())
            $scope.drop_off_latitude = parseFloat($('#drop_off_latitude').val())
            $scope.drop_off_longitude = parseFloat($('#drop_off_longitude').val())
            $scope.from_marker = new google.maps.Marker({
                map: $scope.map,
                draggable: true,
                icon: $scope.from_pin,
                animation: google.maps.Animation.DROP,
                position: { lat: $scope.pick_up_latitude, lng: $scope.pick_up_longitude }
            });
            $scope.to_marker = new google.maps.Marker({
                map: $scope.map,
                icon: $scope.to_pin,
                draggable: true,
                animation: google.maps.Animation.DROP,
                position: { lat: $scope.drop_off_latitude, lng: $scope.drop_off_longitude }
            });


            if ($scope.assigned_driver) {
                console.log($('#driver_id').val());
                $scope.driver_latitude = parseFloat($('#driver_latitude').val());
                $scope.driver_longitude = parseFloat($('#driver_longitude').val());
                $scope.from_marker = new google.maps.Marker({
                    map: $scope.map,
                    draggable: false,
                    icon: $scope.car_pin,
                    animation: google.maps.Animation.DROP,
                    position: { lat: $scope.driver_latitude, lng: $scope.driver_longitude }
                });
            }
            $scope.map.setZoom($scope.mapRadius);
            $scope.map.panTo($scope.to_marker.position);
            $scope.from_marker.addListener('dragend', fromMarkerDrag);
            $scope.to_marker.addListener('dragend', toMarkerDrag);
            calculateAndDisplayRoute();
        }
    }

    //Show route on map
    function calculateAndDisplayRoute() {
        if (typeof $scope.pick_up_latitude === "undefined" || typeof $scope.pick_up_longitude === "undefined" || typeof $scope.drop_off_latitude === "undefined" || typeof $scope.drop_off_longitude === "undefined") {
            return false;
        }
        $scope.directionsService.route({
            origin: {
                lat: parseFloat($scope.pick_up_latitude),
                lng: parseFloat($scope.pick_up_longitude)
            },
            destination: {
                lat: parseFloat($scope.drop_off_latitude),
                lng: parseFloat($scope.drop_off_longitude)
            },
            travelMode: 'DRIVING'
        }, function(response, status) {
            if (status === 'OK') {
                $scope.directionsDisplay.setDirections(response);
                $scope.directionsDisplay.setOptions({ suppressMarkers: true });
            } else {
                console.log(status);
            }
        });
    }

    //Handle markers drag
    function fromMarkerDrag(evt) {
        $scope.pick_up_latitude = evt.latLng.lat()
        $scope.pick_up_longitude = evt.latLng.lng()
        $('#pick_up_latitude').val($scope.pick_up_latitude)
        $('#pick_up_longitude').val($scope.pick_up_longitude)
        var latlng = { lat: $scope.pick_up_latitude, lng: $scope.pick_up_longitude };
        calculateAndDisplayRoute();
        getLocation(latlng, 'input_pick_up_location')
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

    $scope.utc_offset = ''

    //form validation
    // var v = $("#delivery_order").validate({
    //     rules: {
    //         currency_code: { required: true },
    //         mobile_number: { required: true },
    //         first_name: { required: true },
    //         last_name: { required: true },
    //         email: { required: true, email: true },
    //         pick_up_location: { required: true },
    //         drop_off_location: { required: true },
    //         vehicle_type: { required: true },
    //         auto_assign_status: {
    //             required: {
    //                 depends: function(element) {
    //                     if ($('#auto_assign_id').val() == 0) {
    //                         return true;
    //                     } else {
    //                         return false;
    //                     }
    //                 }
    //             }
    //         },
    //         date_time: {
    //             required: true,
    //             min_date_time: true,
    //         },
    //     },
    //     messages: {
    //         auto_assign_status: {
    //             required: 'This field is required if no driver assigned.'
    //         },
    //     },
    //     errorElement: "span",
    //     errorClass: "text-danger",
    //     errorPlacement: function(label, element) {
    //         if (element.attr("data-error-placement") === "container") {
    //             container = element.attr('data-error-container');
    //             $(container).append(label);
    //         } else {
    //             label.insertAfter(element);
    //         }
    //     },
    // });

    // $.validator.addMethod("min_date_time", function(value, element, param) {
    //     if (page == 'edit') {
    //         var old_date_value = new Date(old_edit_date);
    //         var today = new Date().toLocaleString("en-US", { timeZone: "Australia/Melbourne" });
    //         var alignFillDate = new Date($('#input_date_time').val());
    //         if ($scope.utc_offset == '') {
    //             var valid_date = new Date(today.getTime() + (14 * 60 * 1000));
    //             var currenct_date = new Date(today.getTime() + (15 * 60 * 1000));
    //         } else {
    //             var valid_date = new Date(today.getTime() + ((today.getTimezoneOffset() + $scope.utc_offset + 14) * 60000));
    //             var currenct_date = new Date(today.getTime() + ((today.getTimezoneOffset() + $scope.utc_offset + 15) * 60000));
    //         }
    //         if (!moment(old_date_value).isBefore(alignFillDate) && !moment(valid_date).isBefore(alignFillDate)) {
    //             if (moment(old_date_value).isBefore(valid_date))
    //                 $('#input_date_time').val($filter('date')(new Date(old_date_value), 'yyyy-MM-dd HH:mm'))
    //             else
    //                 $('#input_date_time').val($filter('date')(new Date(currenct_date), 'yyyy-MM-dd HH:mm'))
    //         }
    //         var alignFillDate = new Date($('#input_date_time').val());
    //         return moment(old_date_value).isBefore(alignFillDate) || moment(valid_date).isBefore(alignFillDate) || moment(old_date_value).isSame(alignFillDate);
    //     } else {
    //         var today = new Date().toLocaleString("en-US", { timeZone: "Australia/Melbourne" });
    //         var alignFillDate = new Date($('#input_date_time').val());
    //         if ($scope.utc_offset == '') {
    //             var valid_date = new Date(today.getTime() + (14 * 60 * 1000));
    //             var currenct_date = new Date(today.getTime() + (15 * 60 * 1000));
    //         } else {
    //             var valid_date = new Date(today.getTime() + ((today.getTimezoneOffset() + $scope.utc_offset + 14) * 60000));
    //             var currenct_date = new Date(today.getTime() + ((today.getTimezoneOffset() + $scope.utc_offset + 15) * 60000));
    //         }
    //         if (!moment(valid_date).isBefore(alignFillDate)) {
    //             $('#input_date_time').val($filter('date')(new Date(currenct_date), 'yyyy-MM-dd HH:mm'))
    //         }
    //         var alignFillDate = new Date($('#input_date_time').val());
    //         return moment(valid_date).isBefore(alignFillDate);
    //     }
    // }, $.validator.format((page == 'edit') ? "Please make sure that the booking time is ahead of current time" : "Please make sure that the booking time is 15 minutes ahead from pickup location current time"));

    // var input1 = document.getElementById('input_pick_up_location');
    // google.maps.event.addDomListener(input1, 'keydown', function(event) {
    //     if (event.keyCode === 13) {
    //         event.preventDefault();
    //     }
    // });

    // var input2 = document.getElementById('input_drop_off_location');
    // google.maps.event.addDomListener(input2, 'keydown', function(event) {
    //     if (event.keyCode === 13) {
    //         event.preventDefault();
    //     }
    // });

    // $scope.page_loading = 1;

    // $(document).ready(function() {
    //     $scope.page_loading = 0;
    //     $('#country_code_view').val('+' + $('#input_country_code').val())
    //     $('#input_country_code').change(function() {
    //         $('#country_code_view').val('+' + $('#input_country_code').val())
    //     })
    // })

    // $scope.checkInvalidTime = function() {
    //     var today = new Date().toLocaleString("en-US", { timeZone: "Australia/Melbourne" });
    //     var alignFillDate = new Date($('#input_date_time').val());

    //     if ($scope.utc_offset == '') {
    //         var valid_date = new Date(today.getTime() + (14 * 60 * 1000));
    //         var currenct_date = new Date(today.getTime() + (15 * 60 * 1000));
    //     } else {
    //         var valid_date = new Date(today.getTime() + ((today.getTimezoneOffset() + $scope.utc_offset + 14) * 60000));
    //         var currenct_date = new Date(today.getTime() + ((today.getTimezoneOffset() + $scope.utc_offset + 15) * 60000));
    //     }

    //     return !moment(valid_date).isBefore(alignFillDate);
    // }

    // $scope.submitForm = function($event) {
    //     // if ($scope.checkInvalidTime()) {
    //     //     return true;
    //     // }

    //     $('#customer_phone_number').val($("#country_code_view").val() + $("#input_mobile_number").val());
    //     $('#customer_name').val($("#input_first_name").val() + ' ' + $("#input_last_name").val());

    //     var today = new Date(new Date().toLocaleString("en-US", { timeZone: "Australia/Melbourne" }));
    //     var alignFillDate = new Date($('#input_date_time').val());
    //     var diff;
    //     if ($("#created-time").length != 0) {
    //         var createdDate = new Date($("#created-time").val());
    //         diff = Date.dateDiff('m', createdDate, alignFillDate) + 1;
    //     } else {
    //         diff = Date.dateDiff('m', today, alignFillDate);
    //     }

    //     $('#input_date_time').val(diff);

    //     $("form[name='deliveryAddForm']").submit();
    //     //$('.submit_button').attr('disabled', true);
    // };
}]);