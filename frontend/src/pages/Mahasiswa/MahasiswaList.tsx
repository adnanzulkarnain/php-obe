import { useState, useEffect } from 'react';
import { mahasiswaService, type Mahasiswa } from '../../services/mahasiswa.service';
import { prodiService, type Prodi } from '../../services/prodi.service';
import { kurikulumService } from '../../services/kurikulum.service';
import type { Kurikulum } from '../../types/api';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiUsers, FiCheckCircle, FiXCircle } from 'react-icons/fi';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog, useConfirmDialog } from '../../components/ConfirmDialog';
import { AdvancedFilter } from '../../components/AdvancedFilter';

export const MahasiswaList: React.FC = () => {
  const [mahasiswas, setMahasiswas] = useState<Mahasiswa[]>([]);
  const [prodis, setProdis] = useState<Prodi[]>([]);
  const [kurikulums, setKurikulums] = useState<Kurikulum[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState<Record<string, any>>({});
  const [showForm, setShowForm] = useState(false);
  const [editingMahasiswa, setEditingMahasiswa] = useState<Mahasiswa | null>(null);
  const { isOpen, config, confirm, closeDialog } = useConfirmDialog();

  useEffect(() => {
    loadProdis();
    loadKurikulums();
    loadMahasiswas();
  }, []);

  useEffect(() => {
    loadMahasiswas();
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

  const loadKurikulums = async () => {
    try {
      const response = await kurikulumService.getAll();
      if (response.data) {
        setKurikulums(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load kurikulum');
      console.error(error);
    }
  };

  const loadMahasiswas = async () => {
    try {
      setIsLoading(true);
      const params: any = {};
      if (filters.id_prodi) params.id_prodi = filters.id_prodi;
      if (filters.angkatan) params.angkatan = parseInt(filters.angkatan);
      if (filters.status) params.status = filters.status;

      const response = await mahasiswaService.getAll(params);
      if (response.data && Array.isArray(response.data)) {
        // Apply search filter if exists
        let filteredData: Mahasiswa[] = response.data;
        if (filters.search) {
          const searchLower = filters.search.toLowerCase();
          filteredData = response.data.filter((m: Mahasiswa) =>
            m.nama.toLowerCase().includes(searchLower) ||
            m.nim.toLowerCase().includes(searchLower) ||
            m.email?.toLowerCase().includes(searchLower)
          );
        }
        setMahasiswas(filteredData);
      }
    } catch (error: any) {
      toast.error('Failed to load mahasiswa');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (mahasiswa: Mahasiswa) => {
    const confirmed = await confirm({
      type: 'danger',
      title: 'Delete Mahasiswa',
      message: `Are you sure you want to delete mahasiswa "${mahasiswa.nama}" (${mahasiswa.nim})? This action cannot be undone.`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
    });

    if (confirmed) {
      try {
        await mahasiswaService.delete(mahasiswa.nim);
        toast.success('Mahasiswa deleted successfully');
        loadMahasiswas();
      } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to delete mahasiswa');
      }
    }
  };

  // Future: implement status change dropdown in table
  // const handleChangeStatus = async (mahasiswa: Mahasiswa, newStatus: string) => {
  //   try {
  //     await mahasiswaService.changeStatus(mahasiswa.nim, newStatus);
  //     toast.success('Status updated successfully');
  //     loadMahasiswas();
  //   } catch (error: any) {
  //     toast.error(error.response?.data?.message || 'Failed to update status');
  //   }
  // };

  const handleEdit = (mahasiswa: Mahasiswa) => {
    setEditingMahasiswa(mahasiswa);
    setShowForm(true);
  };

  const handleAddNew = () => {
    setEditingMahasiswa(null);
    setShowForm(true);
  };

  const handleFormClose = () => {
    setShowForm(false);
    setEditingMahasiswa(null);
    loadMahasiswas();
  };

  const getStatusBadge = (status: string) => {
    const styles: Record<string, string> = {
      aktif: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
      cuti: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
      lulus: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
      keluar: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
      non_aktif: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };

    return (
      <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[status] || styles.non_aktif}`}>
        {status.toUpperCase().replace('_', ' ')}
      </span>
    );
  };

  if (isLoading && mahasiswas.length === 0) {
    return <SkeletonLoader variant="table" />;
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Mahasiswa Management
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage student data and information
          </p>
        </div>
        <button
          onClick={handleAddNew}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
        >
          <FiPlus className="mr-2" />
          Add Mahasiswa
        </button>
      </div>

      {/* Advanced Filter */}
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
            name: 'angkatan',
            label: 'Angkatan',
            type: 'number',
            placeholder: 'e.g., 2023',
          },
          {
            name: 'status',
            label: 'Status',
            type: 'select',
            options: [
              { value: 'aktif', label: 'Aktif' },
              { value: 'cuti', label: 'Cuti' },
              { value: 'lulus', label: 'Lulus' },
              { value: 'keluar', label: 'Keluar' },
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
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{mahasiswas.length}</p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiCheckCircle className="text-3xl text-green-600 dark:text-green-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Aktif</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {mahasiswas.filter(m => m.status === 'aktif').length}
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
                {mahasiswas.filter(m => m.status === 'cuti').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiCheckCircle className="text-3xl text-blue-600 dark:text-blue-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Lulus</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {mahasiswas.filter(m => m.status === 'lulus').length}
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
                  NIM
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Name
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Email
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Angkatan
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
              ) : mahasiswas.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No mahasiswa found. Click "Add Mahasiswa" to create one.
                  </td>
                </tr>
              ) : (
                mahasiswas.map((mahasiswa) => (
                  <tr key={mahasiswa.nim} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {mahasiswa.nim}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {mahasiswa.nama}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {mahasiswa.email}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {mahasiswa.angkatan}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(mahasiswa.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => handleEdit(mahasiswa)}
                          className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                          title="Edit"
                        >
                          <FiEdit className="text-lg" />
                        </button>
                        <button
                          onClick={() => handleDelete(mahasiswa)}
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
        <MahasiswaFormModal
          mahasiswa={editingMahasiswa}
          prodis={prodis}
          kurikulums={kurikulums}
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

// Mahasiswa Form Modal Component
interface MahasiswaFormModalProps {
  mahasiswa: Mahasiswa | null;
  prodis: Prodi[];
  kurikulums: Kurikulum[];
  onClose: () => void;
}

const MahasiswaFormModal: React.FC<MahasiswaFormModalProps> = ({ mahasiswa, prodis, kurikulums, onClose }) => {
  const [formData, setFormData] = useState({
    nim: mahasiswa?.nim || '',
    id_prodi: mahasiswa?.id_prodi || '',
    id_kurikulum: mahasiswa?.id_kurikulum || 0,
    nama: mahasiswa?.nama || '',
    email: mahasiswa?.email || '',
    angkatan: mahasiswa?.angkatan || new Date().getFullYear(),
    jenis_kelamin: mahasiswa?.jenis_kelamin || 'L',
    no_telepon: mahasiswa?.no_telepon || '',
    alamat: mahasiswa?.alamat || '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (mahasiswa) {
        await mahasiswaService.update(mahasiswa.nim, formData);
        toast.success('Mahasiswa updated successfully');
      } else {
        await mahasiswaService.create(formData);
        toast.success('Mahasiswa created successfully');
      }
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to save mahasiswa');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            {mahasiswa ? 'Edit Mahasiswa' : 'Add New Mahasiswa'}
          </h2>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                NIM <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.nim}
                onChange={(e) => setFormData({ ...formData, nim: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="e.g., 2023010001"
                required
                disabled={!!mahasiswa}
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
                Kurikulum <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.id_kurikulum}
                onChange={(e) => setFormData({ ...formData, id_kurikulum: Number(e.target.value) })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                required
              >
                <option value="0">Select Kurikulum</option>
                {kurikulums.map((k) => (
                  <option key={k.id_kurikulum} value={k.id_kurikulum}>
                    {k.kode_kurikulum} - {k.nama_kurikulum} ({k.tahun_berlaku})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Angkatan <span className="text-red-500">*</span>
              </label>
              <input
                type="number"
                value={formData.angkatan}
                onChange={(e) => setFormData({ ...formData, angkatan: Number(e.target.value) })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Gender
              </label>
              <select
                value={formData.jenis_kelamin}
                onChange={(e) => setFormData({ ...formData, jenis_kelamin: e.target.value as 'L' | 'P' })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
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
              {isSubmitting ? 'Saving...' : mahasiswa ? 'Update Mahasiswa' : 'Create Mahasiswa'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
