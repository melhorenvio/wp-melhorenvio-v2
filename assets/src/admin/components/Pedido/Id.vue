<template>
  <span>
    <div v-safe-html="linkIdSafe"></div>
  </span>
</template>
<script>
import Vue from "vue";
import VueSafeHTML from "vue-safe-html";

Vue.use(VueSafeHTML);

export default {
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
  },
  computed: {
    linkIdSafe() {
      return `<a rel="noreferrer noopener" target="_blank" href="${this.item.link}" >${this.item.id}</a>`;
    },
  },
  methods: {},
  mounted() {},
};
</script>