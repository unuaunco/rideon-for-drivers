<template>
  <div id="app">
    <HereMap  :center="center" />
  </div>
</template>

<script>
import HereMap from './components/HereMap'
export default {
  name: 'app',
  components: {
    HereMap
    // Remove the HelloWorld.vue 
  },
  data() {
return {
    // we are this as prop to the HereMap component 
  center:{ 
    lat: 40.730610, 
    lng: -73.935242
    }
}
  
  }
}
</script>

<style lang="scss">
@import '../sass/apps/tracking_app.scss';
</style>