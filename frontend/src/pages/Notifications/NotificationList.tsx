import { useState, useEffect } from 'react';
import { notificationService } from '../../services/notification.service';
import type { Notification } from '../../types/api';
import { toast } from 'react-toastify';
import { FiBell, FiCheckCircle } from 'react-icons/fi';

export const NotificationList: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showUnreadOnly, setShowUnreadOnly] = useState(false);

  useEffect(() => {
    loadNotifications();
  }, [showUnreadOnly]);

  const loadNotifications = async () => {
    try {
      setIsLoading(true);
      const response = await notificationService.getAll(showUnreadOnly);
      if (response.data) {
        setNotifications(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load notifications');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleMarkAsRead = async (id: number) => {
    try {
      await notificationService.markAsRead(id);
      loadNotifications();
      toast.success('Notification marked as read');
    } catch (error) {
      toast.error('Failed to mark notification as read');
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await notificationService.markAllAsRead();
      loadNotifications();
      toast.success('All notifications marked as read');
    } catch (error) {
      toast.error('Failed to mark all as read');
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Notifications</h1>
          <p className="text-gray-600 mt-1">Stay updated with your activities</p>
        </div>
        <div className="flex items-center space-x-3">
          <label className="flex items-center space-x-2">
            <input
              type="checkbox"
              checked={showUnreadOnly}
              onChange={(e) => setShowUnreadOnly(e.target.checked)}
              className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span className="text-sm text-gray-700">Unread only</span>
          </label>
          <button
            onClick={handleMarkAllAsRead}
            className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            <FiCheckCircle className="mr-2" />
            Mark All as Read
          </button>
        </div>
      </div>

      <div className="space-y-4">
        {notifications.length === 0 ? (
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <FiBell className="mx-auto text-6xl text-gray-300 mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              No notifications
            </h3>
            <p className="text-gray-600">
              {showUnreadOnly
                ? "You don't have any unread notifications"
                : "You don't have any notifications yet"}
            </p>
          </div>
        ) : (
          notifications.map((notification) => (
            <div
              key={notification.id_notification}
              className={`bg-white rounded-lg shadow-sm border border-gray-200 p-6 ${
                notification.is_read === 0 ? 'bg-blue-50 border-blue-200' : ''
              }`}
            >
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center space-x-2">
                    <h3 className="text-lg font-semibold text-gray-900">
                      {notification.judul}
                    </h3>
                    {notification.is_read === 0 && (
                      <span className="px-2 py-1 text-xs font-medium bg-blue-600 text-white rounded-full">
                        NEW
                      </span>
                    )}
                  </div>
                  <p className="text-gray-700 mt-2">{notification.pesan}</p>
                  <div className="flex items-center space-x-4 mt-3">
                    <span className="text-sm text-gray-500">
                      {new Date(notification.created_at).toLocaleString()}
                    </span>
                    {notification.link && (
                      <a
                        href={notification.link}
                        className="text-sm text-primary-600 hover:text-primary-700 font-medium"
                      >
                        View Details →
                      </a>
                    )}
                  </div>
                </div>
                {notification.is_read === 0 && (
                  <button
                    onClick={() => handleMarkAsRead(notification.id_notification)}
                    className="ml-4 p-2 text-gray-400 hover:text-gray-600"
                    title="Mark as read"
                  >
                    <FiCheckCircle className="text-xl" />
                  </button>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};
