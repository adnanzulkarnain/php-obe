import { useAuth } from '../contexts/AuthContext';
import { FiUser, FiMail, FiShield } from 'react-icons/fi';

export const Profile: React.FC = () => {
  const { user } = useAuth();

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Profile</h1>
        <p className="text-gray-600 mt-1">View and manage your profile information</p>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div className="bg-gradient-to-r from-primary-600 to-primary-700 h-32"></div>
        <div className="px-6 pb-6">
          <div className="flex items-end -mt-16 mb-6">
            <div className="w-32 h-32 bg-white rounded-full border-4 border-white shadow-lg flex items-center justify-center">
              <span className="text-5xl font-bold text-primary-600">
                {user?.nama?.charAt(0).toUpperCase() || 'U'}
              </span>
            </div>
            <div className="ml-6 mb-4">
              <h2 className="text-2xl font-bold text-gray-900">
                {user?.nama || user?.username}
              </h2>
              <p className="text-gray-600 capitalize">{user?.role}</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <div>
                <label className="flex items-center text-sm font-medium text-gray-700 mb-2">
                  <FiUser className="mr-2" />
                  Username
                </label>
                <div className="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p className="text-gray-900">{user?.username}</p>
                </div>
              </div>

              <div>
                <label className="flex items-center text-sm font-medium text-gray-700 mb-2">
                  <FiMail className="mr-2" />
                  Email
                </label>
                <div className="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p className="text-gray-900">{user?.email || 'Not provided'}</p>
                </div>
              </div>
            </div>

            <div className="space-y-4">
              <div>
                <label className="flex items-center text-sm font-medium text-gray-700 mb-2">
                  <FiUser className="mr-2" />
                  Full Name
                </label>
                <div className="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p className="text-gray-900">{user?.nama || 'Not provided'}</p>
                </div>
              </div>

              <div>
                <label className="flex items-center text-sm font-medium text-gray-700 mb-2">
                  <FiShield className="mr-2" />
                  Role
                </label>
                <div className="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p className="text-gray-900 capitalize">{user?.role}</p>
                </div>
              </div>
            </div>
          </div>

          <div className="mt-8 pt-6 border-t border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Account Information
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                <p className="text-sm text-gray-600">User ID</p>
                <p className="text-gray-900 font-medium">{user?.id_user}</p>
              </div>
              <div className="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
                <p className="text-sm text-gray-600">Status</p>
                <p className="text-green-600 font-medium">Active</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
