<script setup lang="ts">
import Sidebar from '../components/common/Sidebar.vue'
import Navbar from '../components/common/Navbar.vue'
import ToastContainer from '../components/common/ToastContainer.vue'
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex text-slate-100 overflow-x-hidden">
    <Sidebar />
    <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
      <Navbar />
      <main class="flex-1 p-6 sm:p-8 max-w-7xl w-full mx-auto min-w-0">
        <slot />
      </main>
    </div>
    <ToastContainer />
  </div>
</template>
