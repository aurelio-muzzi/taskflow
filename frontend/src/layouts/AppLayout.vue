<script setup lang="ts">
import Sidebar from '../components/common/Sidebar.vue'
import Navbar from '../components/common/Navbar.vue'
import ToastContainer from '../components/common/ToastContainer.vue'
</script>

<template>
  <div class="h-screen bg-slate-950 flex text-slate-100 overflow-hidden">
    <Sidebar />
    <!-- Coluna Direita (Navbar fixa + Área de Conteúdo rolável) -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
      <Navbar />
      <div class="flex-1 overflow-y-auto">
        <main class="p-6 sm:p-8 w-full min-w-0">
          <slot />
        </main>
      </div>
    </div>
    <ToastContainer />
  </div>
</template>
