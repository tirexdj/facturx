<template>
  <div class="user-profile">
    <h2>User Profile</h2>
    <p><strong>ID:</strong> {{ user.id }}</p>
    <p><strong>Name:</strong> {{ user.name }}</p>
    <p v-if="user.email"><strong>Email:</strong> {{ user.email }}</p>
    <p v-if="!user.email">User email not available.</p>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

// Define an interface for the user data directly in the component for now
interface UserProfileData {
  id: number;
  name: string;
  email?: string;
}

// Sample user data
const user = ref<UserProfileData>({
  id: 1,
  name: "Jane Doe",
  // email: "jane.doe@example.com" // Email is optional
});

// You could add methods here to fetch or update user data
</script>

<style scoped>
.user-profile {
  border: 1px solid #ccc;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 1em;
}
.user-profile p {
  margin: 0.5em 0;
}
</style>
