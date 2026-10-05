import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import { initDragDropTouch } from './utils/dragDropTouch'
import App from './App.vue'

initDragDropTouch()

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

app.mount('#app')
