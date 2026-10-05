<script setup lang="ts">
import Sidebar from '../components/common/Sidebar.vue'
import Navbar from '../components/common/Navbar.vue'
import ToastContainer from '../components/common/ToastContainer.vue'
import { useSidebar } from '../composables/useSidebar'

const { isOpen, close } = useSidebar()
</script>

<template>
  <div class="h-screen bg-slate-950 flex text-slate-100 overflow-hidden relative">
    <!-- Backdrop Overlay para Drawer em Telas Menores e Tablets (< lg) -->
    <transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden"
        @click="close"
        aria-hidden="true"
      />
    </transition>

    <!-- Barra de Navegação Lateral (Gaveta em Tablets/Móveis, Fixa em Desktop) -->
    <Sidebar />

    <!-- Coluna Direita (Navbar fixa + Área de Conteúdo rolável) -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
      <Navbar />
      <div class="flex-1 overflow-y-auto">
        <main class="p-4 sm:p-6 lg:p-8 w-full min-w-0">
          <slot />
        </main>
      </div>
    </div>
    <ToastContainer />
  </div>
</template>

