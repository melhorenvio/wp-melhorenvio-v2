<template>
  <a target="_blank" rel="noreferrer noopener" :href="link">{{ name }}</a>
</template>
<script>
export default {
  props: {
    link: {
      type: String,
      default: () => "",
    },
    name: {
      type: String,
      default: () => "",
    },
  },
};
</script>