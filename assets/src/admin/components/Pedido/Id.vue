<template>
  <a rel="noreferrer noopener" target="_blank" :href="$sanitizeUrl(link)" >{{id}}</a>
</template>

<script>
export default {
  props: {
    link: {
      type: String,
      required: true
    },
    id: {
      type: Number,
      required: true
    },
  }
};
</script>
