<template>
  <span style="padding: 0; display: contents" v-if="!id">{{name}}</span>
  <a v-else target="_blank" rel="noreferrer noopener" :href="$sanitizeUrl(link)">{{name}}</a>
</template>
<script>

export default {
  computed: {
    link() {
      if(this.definedLink) return this.definedLink;

      if (!this.id) return "";

      return `/wp-admin/post.php?post=${this.id}&action=edit`;
    },
  },
  props: {
    definedLink: {
      type: String,
      default: () => null,
    },
    id: {
      type: Number,
      required: false,
    },
    name: {
      type: String,
      default: () => "",
      required: true,
    },
  },
};
</script>
