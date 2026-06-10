<script setup lang="ts">
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md">
      <div class="rounded-lg bg-white p-8 shadow-md">
        <div class="mb-6 text-center">
          <h1 class="text-2xl font-bold text-gray-900">Prescripciones App</h1>
          <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>
