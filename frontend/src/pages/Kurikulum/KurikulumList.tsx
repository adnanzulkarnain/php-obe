import { useState, useEffect } from 'react';
import { kurikulumService } from '../../services/kurikulum.service';
import type { Kurikulum } from '../../types/api';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiEye } from 'react-icons/fi';
import { AdvancedFilter } from '../../components/AdvancedFilter';

export const KurikulumList: React.FC = () => {
  const [kurikulums, setKurikulums] = useState<Kurikulum[]>([]);
  const [filteredKurikulums, setFilteredKurikulums] = useState<Kurikulum[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState<Record<string, any>>({});

  useEffect(() => {
    loadKurikulums();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [kurikulums, filters]);

  const loadKurikulums = async () => {
    try {
      setIsLoading(true);
      const response = await kurikulumService.getAll();
      if (response.data) {
        setKurikulums(response.data);
        setFilteredKurikulums(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load kurikulum');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const applyFilters = () => {
    let filtered = [...kurikulums];

    // Apply year filter
    if (filters.tahun_berlaku) {
      filtered = filtered.filter(
        (k) => k.tahun_berlaku === parseInt(filters.tahun_berlaku)
      );
    }

    // Apply status filter
    if (filters.status) {
      filtered = filtered.filter((k) => k.status === filters.status);
    }

    // Apply search filter
    if (filters.search) {
      const searchLower = filters.search.toLowerCase();
      filtered = filtered.filter(
        (k) =>
          k.kode_kurikulum.toLowerCase().includes(searchLower) ||
          k.nama_kurikulum.toLowerCase().includes(searchLower)
      );
    }

    setFilteredKurikulums(filtered);
  };

  const getStatusBadge = (status: string) => {
    const styles = {
      draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
      approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
      active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
      inactive: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
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

  // Get unique years for filter options
  const uniqueYears = Array.from(new Set(kurikulums.map((k) => k.tahun_berlaku)))
    .sort((a, b) => b - a)
    .map((year) => ({ value: year.toString(), label: year.toString() }));

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Kurikulum</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">Manage curriculum and learning outcomes</p>
        </div>
        <button className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
          <FiPlus className="mr-2" />
          Add Kurikulum
        </button>
      </div>

      {/* Filter Section */}
      <AdvancedFilter
        fields={[
          {
            name: 'tahun_berlaku',
            label: 'Year',
            type: 'select',
            options: uniqueYears,
          },
          {
            name: 'status',
            label: 'Status',
            type: 'select',
            options: [
              { value: 'draft', label: 'Draft' },
              { value: 'approved', label: 'Approved' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ],
          },
        ]}
        onFilterChange={setFilters}
      />

      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead className="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Code
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Name
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Year
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            {filteredKurikulums.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                  No kurikulum found. Click "Add Kurikulum" to create one.
                </td>
              </tr>
            ) : (
              filteredKurikulums.map((kurikulum) => (
                <tr key={kurikulum.id_kurikulum} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                    {kurikulum.kode_kurikulum}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                    {kurikulum.nama_kurikulum}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                    {kurikulum.tahun_berlaku}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getStatusBadge(kurikulum.status)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    <div className="flex items-center space-x-3">
                      <button
                        className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                        title="View"
                      >
                        <FiEye className="text-lg" />
                      </button>
                      <button
                        className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                        title="Edit"
                      >
                        <FiEdit className="text-lg" />
                      </button>
                      <button
                        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
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
