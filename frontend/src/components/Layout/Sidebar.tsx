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
  FiX,
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
  { name: 'Dosen', path: '/dosen', icon: <FiUsers />, roles: ['admin', 'kaprodi'] },
  { name: 'Analytics', path: '/analytics', icon: <FiBarChart2 />, roles: ['admin', 'kaprodi'] },
  { name: 'Notifications', path: '/notifications', icon: <FiBell /> },
];

interface SidebarProps {
  isOpen?: boolean;
  onClose?: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ isOpen = true, onClose }) => {
  const location = useLocation();
  const { user } = useAuth();

  const filteredMenuItems = menuItems.filter(
    (item) => !item.roles || (user && item.roles.includes(user.role))
  );

  const handleLinkClick = () => {
    // Close sidebar on mobile when link is clicked
    if (onClose && window.innerWidth < 1024) {
      onClose();
    }
  };

  return (
    <>
      {/* Mobile Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      {/* Sidebar */}
      <aside
        className={`
          fixed lg:static inset-y-0 left-0 z-50
          bg-white dark:bg-gray-800 w-64 min-h-screen
          shadow-sm border-r border-gray-200 dark:border-gray-700
          transform transition-transform duration-300 ease-in-out
          ${isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
        `}
        aria-label="Sidebar navigation"
      >
        {/* Header */}
        <div className="p-6 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-primary-600 dark:text-primary-400">
              OBE System
            </h1>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Kurikulum Management
            </p>
          </div>

          {/* Close button for mobile */}
          <button
            onClick={onClose}
            className="lg:hidden p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            aria-label="Close menu"
          >
            <FiX className="text-xl" />
          </button>
        </div>

        {/* Navigation */}
        <nav className="mt-6" role="navigation">
          {filteredMenuItems.map((item) => {
            const isActive = location.pathname === item.path;

            return (
              <Link
                key={item.path}
                to={item.path}
                onClick={handleLinkClick}
                className={`
                  flex items-center px-6 py-3 text-sm font-medium transition-colors
                  ${
                    isActive
                      ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 border-r-4 border-primary-600 dark:border-primary-400'
                      : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'
                  }
                `}
                aria-current={isActive ? 'page' : undefined}
              >
                <span className="mr-3 text-lg" aria-hidden="true">{item.icon}</span>
                {item.name}
              </Link>
            );
          })}
        </nav>
      </aside>
    </>
  );
};
