export interface AppNotification {
  id: number;
  user_id: number;
  type: string;
  title: string;
  message: string;
  action_url?: string | null;
  icon?: string | null;
  priority: 'info' | 'normal' | 'high' | 'urgent';
  data?: any;
  is_read: boolean;
  read_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface NotificationResponse {
  unread_count: number;
  notifications: AppNotification[];
}

