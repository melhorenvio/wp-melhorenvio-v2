<template>
  <span>
    <a target="_blank" v-bind:href="item.link"
      ><strong>{{ item.id }}</strong></a
    >
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
  mounted() {},
};
</script>