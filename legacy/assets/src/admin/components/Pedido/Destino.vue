<template>
  <span style="font-size: 14px">
    {{ to.name }}
    <br />
    {{ to.email }}
    <br />
    {{ to.phoneMasked }}
    <br />
    {{ to.address }}, {{ to.number }}
    <template v-if="to.complement">- {{ to.complement }}</template>
    <template v-if="to.district">- {{ to.district }}</template>
    <br />
    {{ to.city }}/{{ to.state_abbr }}
  </span>
</template>
<script>
export default {
  props: {
    to: {
      type: Object,
      default: () => ({}),
    },
  },
};
</script>