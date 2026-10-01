<script setup lang="ts">
import ToastContainer from '../components/common/ToastContainer.vue'
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 selection:bg-emerald-500/20 selection:text-emerald-400">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <slot name="header" />
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
      <div class="bg-slate-900/90 border border-slate-800 py-8 px-6 shadow-2xl rounded-2xl sm:px-10 backdrop-blur-xl">
        <slot />
      </div>
    </div>
    <ToastContainer />
  </div>
</template>
