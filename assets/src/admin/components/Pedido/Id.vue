<template>
  <span>
    <div v-safe-html="linkIdSafe"></div>
  </span>
</template>
<script>

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
};
</script>