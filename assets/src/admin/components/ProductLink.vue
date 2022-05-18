<template>
  <div v-safe-html="customLink" />
</template>
<script>

export default {
  computed: {
    customLink() {

      const {id, definedLink, name} = this;

      if(definedLink) {
        return `<a target="_blank" rel="noreferrer noopener" href="${definedLink}">${name}</a>`;  
      }

      const link = `/wp-admin/post.php?post=${id}&action=edit`;
      return `<a target="_blank" rel="noreferrer noopener" href="${link}">${this.name}</a>`;
    },
  },
  props: {
    id: {
      type: Number,
    },
    definedLink: {
      type: String,
      default: null
    },
    name: {
      type: String,
      default: () => "",
    },
  },
};
</script>