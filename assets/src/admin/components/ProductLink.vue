<template>
  <div v-safe-html="linkProduct" />
</template>
<script>
import Vue from "vue";
import VueSafeHTML from "vue-safe-html";

Vue.use(VueSafeHTML);

export default {
  computed: {
    linkProduct() {
      const link = `/wp-admin/post.php?post=${this.id}&action=edit`;
      return `<a target="_blank" rel="noreferrer noopener" href="${link}">${this.name}</a>`;
    },
  },
  props: {
    id: {
      type: Number,
    },
    name: {
      type: String,
      default: () => "",
    },
  },
};
</script>