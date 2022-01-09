<template>
  <div id="trackingApp" class="container-fluid" v-if="!isLoading">
    <div class="row text-center">
      <a href="https://localhost/tracking">
        <img
          class="dash-head-logo"
          src="https://localhost/images/logos/page_logo.png"
        />
      </a>
      <h3 class="tracking-header">Your Order Tracking</h3>
    </div>

    <div class="row card-wrapper">
      <div class="card card-tracking border-light col-sm-12 col-md-12 col-lg-8">
        <div class="card-header display-6">
          Status: {{ trackingData.orderStatusLabel }}
        </div>

        <div class="card-body">
          <div class="container-fluid card-body-block">
            <div class="row">
              <div class="col-lg-8">
                <p v-if="trackingData.orderStatus !== 'delivered'">
                  {{ trackingData.estLabel }}
                  <strong>{{ trackingData.estimateDeliverTime }}</strong>
                </p>
                <p>
                  Driver name is
                  <strong>{{ trackingData.driverName }}</strong>
                </p>
              </div>
              <div class="col-lg-4 text-center">
                <img
                  width="100"
                  :src="trackingData.driverPhoto"
                  class="text-end"
                  alt="Driver Photo"
                />
              </div>
            </div>
          </div>
          <div class="card-body-block">
            <HereMap
              :trackingData="trackingData"
              :apikey="hereApiKey"
              :driverIcon="driverIcon"
              :sourceIcon="sourceIcon"
              :destinationIcon="destinationIcon"
              v-if="!isLoading"
              class="card-img-top"
            />
          </div>
          <div class="card-body-block">
            <h2 class="card-title mb-4">Order info</h2>
            <p class="card-text">Order # : {{ trackingData.orderId }}</p>
            <p class="card-text">
              Customer Name : {{ trackingData.clientName }}
            </p>
            <p class="card-text">Merchant : {{ trackingData.merchantName }}</p>
            <p class="card-text">
              Drop location : {{ trackingData.dropLocation }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import HereMap from "./components/HereMap";
import axios from "axios";

export default {
  name: "trackingApp",
  components: {
    HereMap,
  },
  data() {
    return {
      // we are this as prop to the HereMap component
      center: {},
      trackingData: null,
      hereApiKey: "XkpdGPT9-NHJ2ZYT_5r9Im2PP3w3iCHOzNiokSPIrRY",
      isLoading: true,
      driverIcon: "/images/car_black.png",
      sourceIcon: "/images/map_pin_grn.png",
      destinationIcon: "/images/map_pin_red.png",
    };
  },
  methods: {
    getTrackingData() {
      axios
        .get(`/api/tracking/${this.getTrackingId()}`)
        .then((res) => {
          this.trackingData = res.data;
          switch (this.trackingData.orderStatus) {
            case "assigned":
              this.trackingData.estLabel = "Estimate time to pick up";
              this.trackingData.orderStatusLabel = "En route to pick up";
              break;
            case "picked_up":
              this.trackingData.estLabel = "Estimate time to drop";
              this.trackingData.orderStatusLabel = "En route to drop off";
              break;
            default:
              this.trackingData.orderStatusLabel = "Order already delivered";
              break;
          }
        })
        .catch((error) => console.log(error))
        .finally(() => (this.isLoading = false));
    },
    getTrackingId() {
      return document.getElementById("tracking-id").value;
    },
  },
  mounted() {
    this.getTrackingData();
  },
};
</script>

<style lang="scss">
@font-face {
  font-family: Montserrat;
  src: url("/fonts/montserrat-extralight.otf");
}

@font-face {
  font-family: MontserratBold;
  src: url("/fonts/montserrat-bold.otf");
}

@font-face {
  font-family: MontserratReg;
  src: url("/fonts/montserrat-regular.otf");
}

@font-face {
  font-family: BebasNeueReg;
  src: url("/fonts/BebasNeue-Regular.otf");
}
//images preloading
body::after {
  position: absolute;
  width: 0;
  height: 0;
  overflow: hidden;
  z-index: -1; // hide images
  content: url("../images/car_black.png") url("../images/map_pin_grn.png")
    url("../images/map_pin_red.png"); // load images
}

@import "../sass/apps/tracking_app.scss";
</style>