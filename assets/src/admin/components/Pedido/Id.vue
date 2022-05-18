<template>
  <span>
    <a target="_blank" v-bind:href="item.link"
      ><strong>{{ item.id }}</strong></a
    >
  </span>
</template>
<script>
import Vue from "vue";
import VueSanitize from "vue-sanitize";

Vue.use(VueSanitize);

export default {
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
  },
  mounted() {
    Vue.use(VueSanitize, this.item);
  },
};
</script>