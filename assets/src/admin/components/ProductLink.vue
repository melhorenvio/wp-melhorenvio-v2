<template>
  <a target="_blank" rel="noreferrer noopener" :href="sanitizeLink">{{
    name
  }}</a>
</template>
<script>
import { sanitizeUrl } from "@braintree/sanitize-url/dist";
export default {
  computed: {
    sanitizeLink() {
      const linkProduct = `/wp-admin/post.php?post=${this.id}&action=edit`;
      return sanitizeUrl(linkProduct);
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