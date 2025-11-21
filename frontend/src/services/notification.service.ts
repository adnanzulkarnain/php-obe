import { api } from './api';
import type { Notification, ApiResponse } from '../types/api';

export const notificationService = {
  async getAll(unreadOnly = false): Promise<ApiResponse<Notification[]> & { meta?: any }> {
    const params = unreadOnly ? '?unread_only=true' : '';
    const response = await api.get<ApiResponse<Notification[]> & { meta?: any }>(`/notifications${params}`);
    return response.data;
  },

  async getUnreadCount(): Promise<ApiResponse<{ unread_count: number }>> {
    const response = await api.get<ApiResponse<{ unread_count: number }>>('/notifications/unread-count');
    return response.data;
  },

  async markAsRead(id: number): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(`/notifications/${id}/read`);
    return response.data;
  },

  async markAllAsRead(): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>('/notifications/read-all');
    return response.data;
  },

  async delete(id: number): Promise<ApiResponse<any>> {
    const response = await api.delete<ApiResponse<any>>(`/notifications/${id}`);
    return response.data;
  },
};
