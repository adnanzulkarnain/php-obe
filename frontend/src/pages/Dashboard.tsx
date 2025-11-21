import { useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { FiBook, FiFileText, FiUsers, FiCheckCircle, FiAlertTriangle } from 'react-icons/fi';
import { useQuery } from '@tanstack/react-query';
import analyticsService, { type DashboardData, type RecentActivity } from '../services/analytics.service';
import { SkeletonLoader } from '../components/SkeletonLoader';
import { toast } from 'react-toastify';

export const Dashboard: React.FC = () => {
  const { user } = useAuth();

  // Fetch dashboard data from API
  const {
    data: dashboardData,
    isLoading,
    error,
  } = useQuery<DashboardData>({
    queryKey: ['dashboard'],
    queryFn: () => analyticsService.getDashboard(),
    retry: 2,
  });

  useEffect(() => {
    if (error) {
      toast.error('Gagal memuat data dashboard');
    }
  }, [error]);

  const statCards = [
    {
      title: 'Total Kelas',
      value: dashboardData?.summary?.total_kelas || 0,
      icon: <FiBook className="text-3xl" />,
      color: 'bg-blue-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
    {
      title: 'Total Mahasiswa',
      value: dashboardData?.summary?.total_mahasiswa || 0,
      icon: <FiUsers className="text-3xl" />,
      color: 'bg-purple-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
    {
      title: 'Nilai Diinput',
      value: dashboardData?.summary?.nilai_diinput || 0,
      icon: <FiCheckCircle className="text-3xl" />,
      color: 'bg-green-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
    {
      title: 'Rata-rata Nilai',
      value: dashboardData?.summary?.rata_nilai
        ? Number(dashboardData.summary.rata_nilai).toFixed(2)
        : '0.00',
      icon: <FiFileText className="text-3xl" />,
      color: 'bg-orange-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
  ];

  const filteredStats = statCards.filter(
    (card) => !card.roles || (user && card.roles.includes(user.role))
  );

  if (isLoading) {
    return (
      <div>
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-2">
            Welcome back, {user?.nama || user?.username}!
          </p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {[1, 2, 3, 4].map((i) => (
            <SkeletonLoader key={i} className="h-32" />
          ))}
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <SkeletonLoader className="h-96" />
          <SkeletonLoader className="h-96" />
        </div>
      </div>
    );
  }

  const formatRelativeTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) return `${diffMins} menit yang lalu`;
    if (diffHours < 24) return `${diffHours} jam yang lalu`;
    return `${diffDays} hari yang lalu`;
  };

  const getActivityColor = (action: string) => {
    switch (action) {
      case 'INSERT':
        return 'bg-green-600';
      case 'UPDATE':
        return 'bg-blue-600';
      case 'DELETE':
        return 'bg-red-600';
      default:
        return 'bg-primary-600';
    }
  };

  const getActivityLabel = (activity: RecentActivity) => {
    const actionMap: Record<string, string> = {
      INSERT: 'Menambahkan',
      UPDATE: 'Mengupdate',
      DELETE: 'Menghapus',
    };
    const tableMap: Record<string, string> = {
      nilai_detail: 'nilai',
      ketercapaian_cpmk: 'ketercapaian CPMK',
      ketercapaian_cpl: 'ketercapaian CPL',
      enrollment: 'enrollment',
    };

    return `${actionMap[activity.action] || activity.action} ${
      tableMap[activity.table_name] || activity.table_name
    }`;
  };

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Welcome back, {user?.nama || user?.username}!
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {filteredStats.map((stat, index) => (
          <div
            key={index}
            className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">{stat.title}</p>
                <p className="text-3xl font-bold text-gray-900 dark:text-white">{stat.value}</p>
              </div>
              <div className={`${stat.color} text-white p-4 rounded-lg`}>
                {stat.icon}
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Activities */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Recent Activities
          </h2>
          <div className="space-y-4">
            {dashboardData?.recent_activity && dashboardData.recent_activity.length > 0 ? (
              dashboardData.recent_activity.map((activity, index) => (
                <div key={index} className="flex items-start space-x-3">
                  <div
                    className={`flex-shrink-0 w-2 h-2 ${getActivityColor(
                      activity.action
                    )} rounded-full mt-2`}
                  ></div>
                  <div>
                    <p className="text-sm text-gray-900 dark:text-white font-medium">
                      {activity.username} {getActivityLabel(activity)}
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {formatRelativeTime(activity.created_at)}
                    </p>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                Tidak ada aktivitas terbaru
              </p>
            )}
          </div>
        </div>

        {/* Alerts */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Alerts
          </h2>
          <div className="space-y-4">
            {dashboardData?.alerts && dashboardData.alerts.length > 0 ? (
              dashboardData.alerts.map((alert, index) => (
                <div
                  key={index}
                  className="flex items-start space-x-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800"
                >
                  <FiAlertTriangle className="flex-shrink-0 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                  <div>
                    <p className="text-sm text-gray-900 dark:text-white font-medium">
                      {alert.message}
                    </p>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                Tidak ada alert
              </p>
            )}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Quick Actions
          </h2>
          <div className="space-y-3">
            {user?.role === 'admin' || user?.role === 'kaprodi' ? (
              <a
                href="/kurikulum"
                className="block w-full text-left px-4 py-3 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors"
              >
                <p className="font-medium">Manage Kurikulum</p>
                <p className="text-sm text-primary-600 dark:text-primary-500">
                  Create or edit curriculum
                </p>
              </a>
            ) : null}
            {user?.role === 'dosen' || user?.role === 'admin' ? (
              <a
                href="/rps"
                className="block w-full text-left px-4 py-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
              >
                <p className="font-medium">Create RPS</p>
                <p className="text-sm text-green-600 dark:text-green-500">
                  Design course learning plans
                </p>
              </a>
            ) : null}
            <a
              href="/notifications"
              className="block w-full text-left px-4 py-3 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
            >
              <p className="font-medium">View Notifications</p>
              <p className="text-sm text-blue-600 dark:text-blue-500">Check recent updates</p>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};
