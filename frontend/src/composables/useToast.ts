import { ref } from 'vue'

export interface ToastMessage {
  id: string
  type: 'success' | 'error' | 'info' | 'warning' | 'confirm'
  title?: string
  text: string
  duration?: number
  confirmText?: string
  cancelText?: string
  resolve?: (value: boolean) => void
}

const toasts = ref<ToastMessage[]>([])

export function useToast() {
  function show(text: string, type: ToastMessage['type'] = 'info', duration = 3500) {
    const id = Math.random().toString(36).substring(2, 9)
    toasts.value.push({ id, type, text, duration })

    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }
    return id
  }

  function success(text: string, duration?: number) {
    show(text, 'success', duration)
  }

  function error(text: string, duration?: number) {
    show(text, 'error', duration)
  }

  function info(text: string, duration?: number) {
    show(text, 'info', duration)
  }

  function warning(text: string, duration?: number) {
    show(text, 'warning', duration)
  }

  function confirm(
    text: string,
    options?: { title?: string; confirmText?: string; cancelText?: string; duration?: number }
  ): Promise<boolean> {
    return new Promise((resolve) => {
      const id = Math.random().toString(36).substring(2, 9)
      const duration = options?.duration ?? 0

      toasts.value.push({
        id,
        type: 'confirm',
        title: options?.title || 'Confirmação',
        text,
        duration,
        confirmText: options?.confirmText || 'Confirmar',
        cancelText: options?.cancelText || 'Cancelar',
        resolve,
      })

      if (duration > 0) {
        setTimeout(() => {
          handleConfirmAction(id, false)
        }, duration)
      }
    })
  }

  function remove(id: string) {
    const item = toasts.value.find((t) => t.id === id)
    if (item && item.resolve) {
      item.resolve(false)
    }
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function handleConfirmAction(id: string, confirmed: boolean) {
    const item = toasts.value.find((t) => t.id === id)
    if (item && item.resolve) {
      item.resolve(confirmed)
    }
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return {
    toasts,
    show,
    success,
    error,
    info,
    warning,
    confirm,
    remove,
    handleConfirmAction,
  }
}

