/**
 * Polyfill leve para sintetizar eventos HTML5 Drag & Drop a partir de Touch Events
 * Permitindo que o quadro Kanban funcione com toque em iPads e tablets Android
 * sem necessidade de pacotes externos no container Docker.
 */

class DragDropTouchPolyfill {
  private _dragSource: HTMLElement | null = null
  private _lastTouch: Touch | null = null
  private _lastTarget: Element | null = null
  private _ptDown: { x: number; y: number } | null = null
  private _isDragging = false
  private _dataTransfer: DataTransfer | null = null

  constructor() {
    if (typeof window === 'undefined' || typeof document === 'undefined') return

    // Registrar listeners globais em modo passivo/não-passivo adequado
    document.addEventListener('touchstart', this._onTouchStart.bind(this), { passive: false })
    document.addEventListener('touchmove', this._onTouchMove.bind(this), { passive: false })
    document.addEventListener('touchend', this._onTouchEnd.bind(this))
    document.addEventListener('touchcancel', this._onTouchEnd.bind(this))
  }

  private _findDraggable(element: Element | null): HTMLElement | null {
    let curr = element
    while (curr && curr !== document.body) {
      if ((curr as HTMLElement).draggable) {
        return curr as HTMLElement
      }
      curr = curr.parentElement
    }
    return null
  }

  private _onTouchStart(e: TouchEvent) {
    if (e.touches.length !== 1) return

    const touch = e.touches[0]
    const target = document.elementFromPoint(touch.clientX, touch.clientY)
    const draggable = this._findDraggable(target)

    if (draggable) {
      this._dragSource = draggable
      this._ptDown = { x: touch.clientX, y: touch.clientY }
      this._lastTouch = touch
    }
  }

  private _onTouchMove(e: TouchEvent) {
    if (!this._dragSource || !this._ptDown || e.touches.length !== 1) return

    const touch = e.touches[0]
    const dx = touch.clientX - this._ptDown.x
    const dy = touch.clientY - this._ptDown.y

    // Limiar de 8px para distinguir clique/scroll de intenção de arrasto
    if (!this._isDragging && Math.sqrt(dx * dx + dy * dy) > 8) {
      this._isDragging = true
      this._dataTransfer = new DataTransfer()

      const dragStartEvt = new DragEvent('dragstart', {
        bubbles: true,
        cancelable: true,
        dataTransfer: this._dataTransfer,
      })

      this._dragSource.dispatchEvent(dragStartEvt)
    }

    if (this._isDragging) {
      e.preventDefault() // Previne rolagem da página enquanto arrasta a tarefa
      this._lastTouch = touch

      const hoverTarget = document.elementFromPoint(touch.clientX, touch.clientY)
      if (hoverTarget) {
        if (hoverTarget !== this._lastTarget) {
          if (this._lastTarget) {
            const dragLeaveEvt = new DragEvent('dragleave', {
              bubbles: true,
              cancelable: true,
              dataTransfer: this._dataTransfer,
            })
            this._lastTarget.dispatchEvent(dragLeaveEvt)
          }

          const dragEnterEvt = new DragEvent('dragenter', {
            bubbles: true,
            cancelable: true,
            dataTransfer: this._dataTransfer,
          })
          hoverTarget.dispatchEvent(dragEnterEvt)
          this._lastTarget = hoverTarget
        }

        const dragOverEvt = new DragEvent('dragover', {
          bubbles: true,
          cancelable: true,
          dataTransfer: this._dataTransfer,
        })
        hoverTarget.dispatchEvent(dragOverEvt)
      }
    }
  }

  private _onTouchEnd(_e: TouchEvent) {
    if (this._isDragging && this._lastTouch && this._dragSource) {
      const dropTarget = document.elementFromPoint(this._lastTouch.clientX, this._lastTouch.clientY)

      if (dropTarget) {
        const dropEvt = new DragEvent('drop', {
          bubbles: true,
          cancelable: true,
          dataTransfer: this._dataTransfer,
        })
        dropTarget.dispatchEvent(dropEvt)
      }

      const dragEndEvt = new DragEvent('dragend', {
        bubbles: true,
        cancelable: true,
        dataTransfer: this._dataTransfer,
      })
      this._dragSource.dispatchEvent(dragEndEvt)
    }

    this._dragSource = null
    this._ptDown = null
    this._isDragging = false
    this._lastTouch = null
    this._lastTarget = null
    this._dataTransfer = null
  }
}

// Inicializa automaticamente no browser
export function initDragDropTouch() {
  if (typeof window !== 'undefined' && ('ontouchstart' in window || navigator.maxTouchPoints > 0)) {
    new DragDropTouchPolyfill()
  }
}
