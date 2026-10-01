export interface InternalNotification {
  id: string
  type: string
  title: string
  message: string
  data?: Record<string, any> | null
  read_at: string | null
  is_read: boolean
  created_at: string
}

export interface NotificationListResponse {
  items: InternalNotification[]
  unread_count: number
}
