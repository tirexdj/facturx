<template>
  <div>
    <h1>Main Page</h1>
    <p>This page displays the UserProfile component.</p>
    <UserProfile />
    <hr>
    <p>You can add more content here.</p>
  </div>
</template>

<script lang="ts" setup>
// Import the component
import UserProfile from '~/components/UserProfile.vue';

// No additional page-specific script needed for this simple example
</script>

<style scoped>
h1 {
  color: #333;
}
p {
  line-height: 1.6;
}
</style>
