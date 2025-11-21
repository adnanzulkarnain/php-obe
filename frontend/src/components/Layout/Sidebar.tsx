import { Link, useLocation } from 'react-router-dom';
import {
  FiHome,
  FiBook,
  FiBookOpen,
  FiClipboard,
  FiUsers,
  FiBarChart2,
  FiBell,
  FiFileText,
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';

interface MenuItem {
  name: string;
  path: string;
  icon: React.ReactNode;
  roles?: string[];
}

const menuItems: MenuItem[] = [
  { name: 'Dashboard', path: '/', icon: <FiHome /> },
  { name: 'Kurikulum', path: '/kurikulum', icon: <FiBook />, roles: ['admin', 'kaprodi'] },
  { name: 'CPL', path: '/cpl', icon: <FiBookOpen />, roles: ['admin', 'kaprodi', 'dosen'] },
  { name: 'CPMK', path: '/cpmk', icon: <FiClipboard />, roles: ['admin', 'kaprodi', 'dosen'] },
  { name: 'RPS', path: '/rps', icon: <FiFileText />, roles: ['admin', 'kaprodi', 'dosen'] },
  { name: 'Penilaian', path: '/penilaian', icon: <FiBarChart2 />, roles: ['admin', 'dosen'] },
  { name: 'Mahasiswa', path: '/mahasiswa', icon: <FiUsers />, roles: ['admin', 'kaprodi', 'dosen'] },
  { name: 'Analytics', path: '/analytics', icon: <FiBarChart2 />, roles: ['admin', 'kaprodi'] },
  { name: 'Notifications', path: '/notifications', icon: <FiBell /> },
];

export const Sidebar: React.FC = () => {
  const location = useLocation();
  const { user } = useAuth();

  const filteredMenuItems = menuItems.filter(
    (item) => !item.roles || (user && item.roles.includes(user.role))
  );

  return (
    <aside className="bg-white w-64 min-h-screen shadow-sm border-r border-gray-200">
      <div className="p-6">
        <h1 className="text-2xl font-bold text-primary-600">OBE System</h1>
        <p className="text-sm text-gray-500 mt-1">Kurikulum Management</p>
      </div>

      <nav className="mt-6">
        {filteredMenuItems.map((item) => {
          const isActive = location.pathname === item.path;

          return (
            <Link
              key={item.path}
              to={item.path}
              className={`
                flex items-center px-6 py-3 text-sm font-medium transition-colors
                ${
                  isActive
                    ? 'bg-primary-50 text-primary-600 border-r-4 border-primary-600'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                }
              `}
            >
              <span className="mr-3 text-lg">{item.icon}</span>
              {item.name}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
};
