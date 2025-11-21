import { useEffect, useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { FiBook, FiFileText, FiUsers, FiCheckCircle } from 'react-icons/fi';

interface DashboardStats {
  total_kurikulum: number;
  total_rps: number;
  total_mahasiswa: number;
  total_cpl: number;
}

export const Dashboard: React.FC = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState<DashboardStats>({
    total_kurikulum: 0,
    total_rps: 0,
    total_mahasiswa: 0,
    total_cpl: 0,
  });

  useEffect(() => {
    // In a real app, fetch stats from API
    // For now, using mock data
    setStats({
      total_kurikulum: 5,
      total_rps: 42,
      total_mahasiswa: 150,
      total_cpl: 28,
    });
  }, []);

  const statCards = [
    {
      title: 'Total Kurikulum',
      value: stats.total_kurikulum,
      icon: <FiBook className="text-3xl" />,
      color: 'bg-blue-500',
      roles: ['admin', 'kaprodi'],
    },
    {
      title: 'Total RPS',
      value: stats.total_rps,
      icon: <FiFileText className="text-3xl" />,
      color: 'bg-green-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
    {
      title: 'Total Mahasiswa',
      value: stats.total_mahasiswa,
      icon: <FiUsers className="text-3xl" />,
      color: 'bg-purple-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
    {
      title: 'Total CPL',
      value: stats.total_cpl,
      icon: <FiCheckCircle className="text-3xl" />,
      color: 'bg-orange-500',
      roles: ['admin', 'kaprodi', 'dosen'],
    },
  ];

  const filteredStats = statCards.filter(
    (card) => !card.roles || (user && card.roles.includes(user.role))
  );

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-600 mt-2">
          Welcome back, {user?.nama || user?.username}!
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {filteredStats.map((stat, index) => (
          <div
            key={index}
            className="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600 mb-1">{stat.title}</p>
                <p className="text-3xl font-bold text-gray-900">{stat.value}</p>
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
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">
            Recent Activities
          </h2>
          <div className="space-y-4">
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-2 h-2 bg-primary-600 rounded-full mt-2"></div>
              <div>
                <p className="text-sm text-gray-900 font-medium">
                  New RPS submitted for review
                </p>
                <p className="text-xs text-gray-500">2 hours ago</p>
              </div>
            </div>
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-2 h-2 bg-green-600 rounded-full mt-2"></div>
              <div>
                <p className="text-sm text-gray-900 font-medium">
                  Kurikulum approved by Kaprodi
                </p>
                <p className="text-xs text-gray-500">5 hours ago</p>
              </div>
            </div>
            <div className="flex items-start space-x-3">
              <div className="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
              <div>
                <p className="text-sm text-gray-900 font-medium">
                  New CPL added to Kurikulum 2024
                </p>
                <p className="text-xs text-gray-500">1 day ago</p>
              </div>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">
            Quick Actions
          </h2>
          <div className="space-y-3">
            {user?.role === 'admin' || user?.role === 'kaprodi' ? (
              <a
                href="/kurikulum"
                className="block w-full text-left px-4 py-3 bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition-colors"
              >
                <p className="font-medium">Manage Kurikulum</p>
                <p className="text-sm text-primary-600">
                  Create or edit curriculum
                </p>
              </a>
            ) : null}
            {user?.role === 'dosen' || user?.role === 'admin' ? (
              <a
                href="/rps"
                className="block w-full text-left px-4 py-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors"
              >
                <p className="font-medium">Create RPS</p>
                <p className="text-sm text-green-600">
                  Design course learning plans
                </p>
              </a>
            ) : null}
            <a
              href="/notifications"
              className="block w-full text-left px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors"
            >
              <p className="font-medium">View Notifications</p>
              <p className="text-sm text-blue-600">Check recent updates</p>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};
