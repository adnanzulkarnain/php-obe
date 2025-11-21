import { useState, useEffect } from 'react';
import { penilaianService, type KomponenPenilaian } from '../../services/penilaian.service';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiBarChart2, FiCheckSquare } from 'react-icons/fi';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog, useConfirmDialog } from '../../components/ConfirmDialog';

export const PenilaianList: React.FC = () => {
  const [komponens, setKomponens] = useState<KomponenPenilaian[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedKelas] = useState<number>(1); // Demo: hardcoded
  const [showForm, setShowForm] = useState(false);
  const [editingKomponen, setEditingKomponen] = useState<KomponenPenilaian | null>(null);
  const { isOpen, config, confirm, closeDialog } = useConfirmDialog();

  useEffect(() => {
    if (selectedKelas) {
      loadKomponens();
    }
  }, [selectedKelas]);

  const loadKomponens = async () => {
    try {
      setIsLoading(true);
      const response = await penilaianService.getKomponenByKelas(selectedKelas);
      if (response.data && Array.isArray(response.data)) {
        setKomponens(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load komponen penilaian');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (komponen: KomponenPenilaian) => {
    const confirmed = await confirm({
      type: 'danger',
      title: 'Delete Komponen',
      message: `Delete "${komponen.nama_komponen}"?`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
    });

    if (confirmed) {
      try {
        await penilaianService.deleteKomponen(komponen.id_komponen);
        toast.success('Komponen deleted successfully');
        loadKomponens();
      } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to delete');
      }
    }
  };

  if (isLoading && komponens.length === 0) {
    return <SkeletonLoader variant="table" />;
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Penilaian & Grading
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage assessment components and grades
          </p>
        </div>
        <button
          onClick={() => { setEditingKomponen(null); setShowForm(true); }}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
        >
          <FiPlus className="mr-2" />
          Add Komponen
        </button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiBarChart2 className="text-3xl text-primary-600 dark:text-primary-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Total Komponen</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{komponens.length}</p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiCheckSquare className="text-3xl text-green-600 dark:text-green-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Total Bobot</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {komponens.reduce((sum, k) => sum + k.bobot, 0)}%
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiBarChart2 className="text-3xl text-blue-600 dark:text-blue-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Avg Bobot</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {komponens.length > 0 ? (komponens.reduce((sum, k) => sum + k.bobot, 0) / komponens.length).toFixed(1) : 0}%
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
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Komponen
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Bobot (%)
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Deskripsi
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Tanggal
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              {isLoading ? (
                <tr><td colSpan={5} className="px-6 py-4"><SkeletonLoader variant="list" /></td></tr>
              ) : komponens.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No komponen penilaian found.
                  </td>
                </tr>
              ) : (
                komponens.map((komponen) => (
                  <tr key={komponen.id_komponen} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                      {komponen.nama_komponen}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {komponen.bobot}%
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                      {komponen.deskripsi || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {komponen.tanggal_penilaian ? new Date(komponen.tanggal_penilaian).toLocaleDateString() : '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => { setEditingKomponen(komponen); setShowForm(true); }}
                          className="text-green-600 hover:text-green-900 dark:text-green-400"
                          title="Edit"
                        >
                          <FiEdit className="text-lg" />
                        </button>
                        <button
                          onClick={() => handleDelete(komponen)}
                          className="text-red-600 hover:text-red-900 dark:text-red-400"
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

      {showForm && (
        <KomponenFormModal
          komponen={editingKomponen}
          kelasId={selectedKelas}
          onClose={() => { setShowForm(false); setEditingKomponen(null); loadKomponens(); }}
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

// Komponen Form Modal
interface KomponenFormModalProps {
  komponen: KomponenPenilaian | null;
  kelasId: number;
  onClose: () => void;
}

const KomponenFormModal: React.FC<KomponenFormModalProps> = ({ komponen, kelasId, onClose }) => {
  const [formData, setFormData] = useState({
    nama_komponen: komponen?.nama_komponen || '',
    bobot: komponen?.bobot || 0,
    deskripsi: komponen?.deskripsi || '',
    tanggal_penilaian: komponen?.tanggal_penilaian || '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (komponen) {
        await penilaianService.updateKomponen(komponen.id_komponen, formData);
        toast.success('Komponen updated successfully');
      } else {
        await penilaianService.createKomponen({
          ...formData,
          id_kelas: kelasId,
          id_template: 1, // Demo
        });
        toast.success('Komponen created successfully');
      }
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to save');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            {komponen ? 'Edit Komponen' : 'Add Komponen'}
          </h2>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nama Komponen <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={formData.nama_komponen}
              onChange={(e) => setFormData({ ...formData, nama_komponen: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="e.g., UTS, UAS, Quiz"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Bobot (%) <span className="text-red-500">*</span>
            </label>
            <input
              type="number"
              value={formData.bobot}
              onChange={(e) => setFormData({ ...formData, bobot: Number(e.target.value) })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              min="0"
              max="100"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Deskripsi
            </label>
            <textarea
              value={formData.deskripsi}
              onChange={(e) => setFormData({ ...formData, deskripsi: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              rows={3}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tanggal Penilaian
            </label>
            <input
              type="date"
              value={formData.tanggal_penilaian}
              onChange={(e) => setFormData({ ...formData, tanggal_penilaian: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>

          <div className="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Saving...' : komponen ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
