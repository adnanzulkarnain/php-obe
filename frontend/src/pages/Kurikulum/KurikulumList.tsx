import { useState, useEffect } from 'react';
import { kurikulumService } from '../../services/kurikulum.service';
import type { Kurikulum } from '../../types/api';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiEye } from 'react-icons/fi';

export const KurikulumList: React.FC = () => {
  const [kurikulums, setKurikulums] = useState<Kurikulum[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadKurikulums();
  }, []);

  const loadKurikulums = async () => {
    try {
      setIsLoading(true);
      const response = await kurikulumService.getAll();
      if (response.data) {
        setKurikulums(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load kurikulum');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const styles = {
      draft: 'bg-gray-100 text-gray-800',
      approved: 'bg-green-100 text-green-800',
      active: 'bg-blue-100 text-blue-800',
      inactive: 'bg-red-100 text-red-800',
    };

    return (
      <span
        className={`px-2 py-1 text-xs font-medium rounded-full ${
          styles[status as keyof typeof styles] || styles.draft
        }`}
      >
        {status.toUpperCase()}
      </span>
    );
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
          <h1 className="text-3xl font-bold text-gray-900">Kurikulum</h1>
          <p className="text-gray-600 mt-1">Manage curriculum and learning outcomes</p>
        </div>
        <button className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
          <FiPlus className="mr-2" />
          Add Kurikulum
        </button>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Code
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Name
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Year
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {kurikulums.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-6 py-8 text-center text-gray-500">
                  No kurikulum found. Click "Add Kurikulum" to create one.
                </td>
              </tr>
            ) : (
              kurikulums.map((kurikulum) => (
                <tr key={kurikulum.id_kurikulum} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {kurikulum.kode_kurikulum}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {kurikulum.nama_kurikulum}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {kurikulum.tahun_berlaku}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getStatusBadge(kurikulum.status)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div className="flex items-center space-x-3">
                      <button
                        className="text-blue-600 hover:text-blue-900"
                        title="View"
                      >
                        <FiEye className="text-lg" />
                      </button>
                      <button
                        className="text-green-600 hover:text-green-900"
                        title="Edit"
                      >
                        <FiEdit className="text-lg" />
                      </button>
                      <button
                        className="text-red-600 hover:text-red-900"
                        title="Delete"
                      >
                        <FiTrash2 className="text-lg" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
