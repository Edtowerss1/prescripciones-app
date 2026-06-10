<script setup lang="ts">
import { RouterView } from 'vue-router'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
</script>

<template>
  <ToastContainer />
  <RouterView v-slot="{ Component, route }">
    <Transition name="fade" mode="out-in">
      <AuthLayout v-if="route.name === 'login'">
        <component :is="Component" />
      </AuthLayout>
      <DashboardLayout v-else-if="route.meta?.requiresAuth">
        <component :is="Component" />
      </DashboardLayout>
      <component v-else :is="Component" />
    </Transition>
  </RouterView>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
