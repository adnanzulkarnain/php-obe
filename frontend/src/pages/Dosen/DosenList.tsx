import { useState, useEffect } from 'react';
import { dosenService, type Dosen } from '../../services/dosen.service';
import { prodiService, type Prodi } from '../../services/prodi.service';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiUsers, FiCheckCircle, FiXCircle } from 'react-icons/fi';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog, useConfirmDialog } from '../../components/ConfirmDialog';
import { AdvancedFilter } from '../../components/AdvancedFilter';

export const DosenList: React.FC = () => {
  const [dosens, setDosens] = useState<Dosen[]>([]);
  const [prodis, setProdis] = useState<Prodi[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState<Record<string, any>>({});
  const [showForm, setShowForm] = useState(false);
  const [editingDosen, setEditingDosen] = useState<Dosen | null>(null);
  const { isOpen, config, confirm, closeDialog } = useConfirmDialog();

  useEffect(() => {
    loadProdis();
    loadDosens();
  }, []);

  useEffect(() => {
    loadDosens();
  }, [filters]);

  const loadProdis = async () => {
    try {
      const response = await prodiService.getAll();
      if (response.data && Array.isArray(response.data)) {
        setProdis(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load prodi');
      console.error(error);
    }
  };

  const loadDosens = async () => {
    try {
      setIsLoading(true);
      const params: any = {};
      if (filters.id_prodi) params.id_prodi = filters.id_prodi;
      if (filters.status) params.status = filters.status;

      const response = await dosenService.getAll(params);
      if (response.data && Array.isArray(response.data)) {
        // Apply search filter if exists
        let filteredData: Dosen[] = response.data;
        if (filters.search) {
          const searchLower = filters.search.toLowerCase();
          filteredData = response.data.filter((d: Dosen) =>
            d.nama.toLowerCase().includes(searchLower) ||
            d.nidn.toLowerCase().includes(searchLower) ||
            d.email?.toLowerCase().includes(searchLower)
          );
        }
        setDosens(filteredData);
      }
    } catch (error: any) {
      toast.error('Failed to load dosen');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (dosen: Dosen) => {
    const confirmed = await confirm({
      type: 'danger',
      title: 'Delete Dosen',
      message: `Are you sure you want to delete dosen "${dosen.nama}" (${dosen.nidn})? This action cannot be undone.`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
    });

    if (confirmed) {
      try {
        await dosenService.delete(dosen.id_dosen);
        toast.success('Dosen deleted successfully');
        loadDosens();
      } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to delete dosen');
      }
    }
  };

  const handleEdit = (dosen: Dosen) => {
    setEditingDosen(dosen);
    setShowForm(true);
  };

  const handleAddNew = () => {
    setEditingDosen(null);
    setShowForm(true);
  };

  const handleFormClose = () => {
    setShowForm(false);
    setEditingDosen(null);
    loadDosens();
  };

  const getStatusBadge = (status: string) => {
    const styles: Record<string, string> = {
      aktif: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
      cuti: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
      pensiun: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
      non_aktif: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };

    return (
      <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[status] || styles.non_aktif}`}>
        {status.toUpperCase().replace('_', ' ')}
      </span>
    );
  };

  if (isLoading && dosens.length === 0) {
    return <SkeletonLoader variant="table" />;
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Dosen Management
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage lecturer data and information
          </p>
        </div>
        <button
          onClick={handleAddNew}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
        >
          <FiPlus className="mr-2" />
          Add Dosen
        </button>
      </div>

      {/* Filter Section */}
      <AdvancedFilter
        fields={[
          {
            name: 'id_prodi',
            label: 'Program Studi',
            type: 'select',
            options: prodis.map((p) => ({
              value: p.id_prodi,
              label: `${p.nama_prodi} (${p.jenjang})`,
            })),
          },
          {
            name: 'status',
            label: 'Status',
            type: 'select',
            options: [
              { value: 'aktif', label: 'Aktif' },
              { value: 'cuti', label: 'Cuti' },
              { value: 'pensiun', label: 'Pensiun' },
              { value: 'non_aktif', label: 'Non-Aktif' },
            ],
          },
        ]}
        onFilterChange={setFilters}
      />

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiUsers className="text-3xl text-primary-600 dark:text-primary-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Total</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{dosens.length}</p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiCheckCircle className="text-3xl text-green-600 dark:text-green-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Aktif</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {dosens.filter(d => d.status === 'aktif').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiXCircle className="text-3xl text-yellow-600 dark:text-yellow-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Cuti</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {dosens.filter(d => d.status === 'cuti').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiCheckCircle className="text-3xl text-blue-600 dark:text-blue-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Pensiun</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {dosens.filter(d => d.status === 'pensiun').length}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead className="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  NIDN
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Name
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Email
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Jabatan
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
              {isLoading ? (
                <tr>
                  <td colSpan={6} className="px-6 py-4">
                    <SkeletonLoader variant="list" />
                  </td>
                </tr>
              ) : dosens.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No dosen found. Click "Add Dosen" to create one.
                  </td>
                </tr>
              ) : (
                dosens.map((dosen) => (
                  <tr key={dosen.id_dosen} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {dosen.nidn}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {dosen.nama}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {dosen.email}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {dosen.jabatan_fungsional || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(dosen.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => handleEdit(dosen)}
                          className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                          title="Edit"
                        >
                          <FiEdit className="text-lg" />
                        </button>
                        <button
                          onClick={() => handleDelete(dosen)}
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

      {/* Form Modal */}
      {showForm && (
        <DosenFormModal
          dosen={editingDosen}
          prodis={prodis}
          onClose={handleFormClose}
        />
      )}

      {isOpen && (
        <ConfirmDialog
          {...config}
          isOpen={isOpen}
          onConfirm={() => {}}
          onCancel={closeDialog}
        />
      )}
    </div>
  );
};

// Dosen Form Modal Component
interface DosenFormModalProps {
  dosen: Dosen | null;
  prodis: Prodi[];
  onClose: () => void;
}

const DosenFormModal: React.FC<DosenFormModalProps> = ({ dosen, prodis, onClose }) => {
  const [formData, setFormData] = useState({
    nidn: dosen?.nidn || '',
    id_prodi: dosen?.id_prodi || '',
    nama: dosen?.nama || '',
    email: dosen?.email || '',
    no_telepon: dosen?.no_telepon || '',
    alamat: dosen?.alamat || '',
    pendidikan_terakhir: dosen?.pendidikan_terakhir || '',
    jabatan_fungsional: dosen?.jabatan_fungsional || '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (dosen) {
        await dosenService.update(dosen.id_dosen, formData);
        toast.success('Dosen updated successfully');
      } else {
        await dosenService.create(formData);
        toast.success('Dosen created successfully');
      }
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to save dosen');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            {dosen ? 'Edit Dosen' : 'Add New Dosen'}
          </h2>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                NIDN <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.nidn}
                onChange={(e) => setFormData({ ...formData, nidn: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="e.g., 0123456789"
                required
                disabled={!!dosen}
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Full Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.nama}
                onChange={(e) => setFormData({ ...formData, nama: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="Enter full name"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email <span className="text-red-500">*</span>
              </label>
              <input
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="email@example.com"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Program Studi <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.id_prodi}
                onChange={(e) => setFormData({ ...formData, id_prodi: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                required
              >
                <option value="">Select Prodi</option>
                {prodis.map((p) => (
                  <option key={p.id_prodi} value={p.id_prodi}>
                    {p.nama_prodi} ({p.jenjang})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Phone Number
              </label>
              <input
                type="text"
                value={formData.no_telepon}
                onChange={(e) => setFormData({ ...formData, no_telepon: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="08xxxxxxxxxx"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Pendidikan Terakhir
              </label>
              <select
                value={formData.pendidikan_terakhir}
                onChange={(e) => setFormData({ ...formData, pendidikan_terakhir: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">Select</option>
                <option value="S1">S1</option>
                <option value="S2">S2</option>
                <option value="S3">S3</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Jabatan Fungsional
              </label>
              <select
                value={formData.jabatan_fungsional}
                onChange={(e) => setFormData({ ...formData, jabatan_fungsional: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">Select</option>
                <option value="Asisten Ahli">Asisten Ahli</option>
                <option value="Lektor">Lektor</option>
                <option value="Lektor Kepala">Lektor Kepala</option>
                <option value="Guru Besar">Guru Besar</option>
              </select>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Address
            </label>
            <textarea
              value={formData.alamat}
              onChange={(e) => setFormData({ ...formData, alamat: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              rows={3}
              placeholder="Enter address..."
            />
          </div>

          <div className="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Saving...' : dosen ? 'Update Dosen' : 'Create Dosen'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
