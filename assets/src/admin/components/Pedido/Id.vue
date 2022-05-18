<template>
  <span>
    <a target="_blank" v-bind:href="sanitize(item.link)"
      ><strong>{{ sanitize(item.id) }}</strong></a
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
  methods: {
    sanitize(value) {
      return this.$sanitize(value);
    },
  },
  mounted() {},
};
</script>