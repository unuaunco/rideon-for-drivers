
<template>
  <div id="map">
    <!--In the following div the HERE Map will render-->
    <div
      id="mapContainer"
      style="height: 600px; width: 100%"
      ref="hereMap"
    ></div>
  </div>
</template>

<script>
export default {
  name: "HereMap",
  props: {
    apikey: String,
    driverIcon: String,
    sourceIcon: String,
    destinationIcon: String,
    trackingData: Object,
    updateTrackingDataCallback: Function,
  },
  data() {
    return {
      platform: null,
      map: null,
    };
  },
  async mounted() {
    // Initialize the platform object:
    const platform = new window.H.service.Platform({
      apikey: this.apikey,
    });
    this.platform = platform;
    this.initializeHereMap();
  },
  methods: {
    /**
     * Initialize map
     */
    initializeHereMap() {
      // rendering map

      const mapContainer = this.$refs.hereMap;
      const H = window.H;
      // Obtain the default map types from the platform object
      var maptypes = this.platform.createDefaultLayers();

      // Instantiate (and display) a map object:

      var map = new H.Map(mapContainer, maptypes.vector.normal.map, {
        zoom: 12,
        center: {
          lat: this.trackingData.dropLatitude,
          lng: this.trackingData.dropLongitude,
        },
      });

      // Initialize markers with icons
      let fromIcon = new H.map.Icon(this.sourceIcon);
      let toIcon = new H.map.Icon(this.destinationIcon);
      let drvIcon = new H.map.Icon(this.driverIcon);

      var markersGroup = new H.map.Group();

      if (this.trackingData.orderStatus !== "delivered") {
        var drvMarker = new H.map.Marker(
          {
            lat: this.trackingData.driverLatitude,
            lng: this.trackingData.driverLongitude,
          },
          {
            icon: drvIcon,
          }
        );
        markersGroup.addObject(drvMarker);
      }

      var fromMarker = new H.map.Marker(
        {
          lat: this.trackingData.pickupLatitude,
          lng: this.trackingData.pickupLongitude,
        },
        {
          icon: fromIcon,
        }
      );
      markersGroup.addObject(fromMarker);

      var toMarker = new H.map.Marker(
        {
          lat: this.trackingData.dropLatitude,
          lng: this.trackingData.dropLongitude,
        },
        {
          icon: toIcon,
        }
      );
      markersGroup.addObject(toMarker);

      // init events

      addEventListener("resize", () => map.getViewPort().resize());

      // add behavior control
      new H.mapevents.Behavior(new H.mapevents.MapEvents(map));

      // add UI
      var ui = H.ui.UI.createDefault(map, maptypes);

      ui.getControl("mapsettings").setDisabled(true);
      ui.getControl("mapsettings").setVisibility(false);

      map.addObject(markersGroup);

      map.getViewModel().setLookAtData({
        bounds: markersGroup.getBoundingBox(),
      });

      this.map = map;

      this.calculateRouteFromAtoB(this.platform);
    },
    /**
     * Calculate the driving route.
     * @param   {H.service.Platform} platform    A stub class to access HERE services
     */
    calculateRouteFromAtoB(platform) {
      let orDes = this.getOriginDestination();
      if (this.trackingData)
        var router = platform.getRoutingService(null, 8),
          routeRequestParams = {
            routingMode: "fast",
            transportMode: "car",
            origin: `${orDes.origin.lat},${orDes.origin.lng}`,
            destination: `${orDes.destination.lat},${orDes.destination.lng}`,
            return: "polyline,elevation", // explicitly request altitude data
          };

      router.calculateRoute(
        routeRequestParams,
        (result) => {
          let route = result.routes[0];

          route.sections.forEach((section) => {
            // decode LineString from the flexible polyline
            let linestring = H.geo.LineString.fromFlexiblePolyline(
              section.polyline
            );

            // Create a polyline to display the route:
            let polyline = new H.map.Polyline(linestring, {
              style: {
                lineWidth: 8,
                strokeColor: "rgba(0, 128, 255, 0.7) ",
              },
            });

            // Add the polyline to the map
            this.map.addObject(polyline);
            // And zoom to its bounding rectangle
            this.map.getViewModel().setLookAtData({
              zoom: this.map.getViewModel().getLookAtData().zoom - 1,
              bounds: polyline.getBoundingBox(),
            });
          });
        },
        (error) => {
          console.log(error);
        }
      );
    },
    /**
     * Get origin and destination depends on current status
     */
    getOriginDestination() {
      let orDes = {
        origin: {
          lat: null,
          lng: null,
        },
        destination: {
          lat: null,
          lng: null,
        },
      };

      switch (this.trackingData.orderStatus) {
        case "assigned":
          orDes.origin.lat = this.trackingData.driverLatitude;
          orDes.origin.lng = this.trackingData.driverLongitude;
          orDes.destination.lat = this.trackingData.pickupLatitude;
          orDes.destination.lng = this.trackingData.pickupLongitude;
          break;
        case "picked_up":
          orDes.origin.lat = this.trackingData.driverLatitude;
          orDes.origin.lng = this.trackingData.driverLongitude;
          orDes.destination.lat = this.trackingData.dropLatitude;
          orDes.destination.lng = this.trackingData.dropLongitude;
          break;
        default:
          orDes.origin.lat = this.trackingData.pickupLatitude;
          orDes.origin.lng = this.trackingData.pickupLongitude;
          orDes.destination.lat = this.trackingData.dropLatitude;
          orDes.destination.lng = this.trackingData.dropLongitude;
          break;
      }

      return orDes;
    },
  },
};
</script>

<style lang="scss">
@import "../../sass/components/_heremap.scss";
</style>