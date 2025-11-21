import { useState, useEffect } from 'react';
import { cplService } from '../../services/cpl.service';
import { kurikulumService } from '../../services/kurikulum.service';
import type { CPL, Kurikulum } from '../../types/api';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiFilter } from 'react-icons/fi';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog, useConfirmDialog } from '../../components/ConfirmDialog';

export const CPLList: React.FC = () => {
  const [cpls, setCpls] = useState<CPL[]>([]);
  const [kurikulums, setKurikulums] = useState<Kurikulum[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedKurikulum, setSelectedKurikulum] = useState<number | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editingCPL, setEditingCPL] = useState<CPL | null>(null);
  const { isOpen, config, confirm, closeDialog } = useConfirmDialog();

  useEffect(() => {
    loadKurikulums();
  }, []);

  useEffect(() => {
    if (selectedKurikulum) {
      loadCPLs();
    }
  }, [selectedKurikulum]);

  const loadKurikulums = async () => {
    try {
      const response = await kurikulumService.getAll();
      if (response.data) {
        setKurikulums(response.data);
        // Auto-select active kurikulum
        const activeKurikulum = response.data.find(k => k.status === 'active');
        if (activeKurikulum) {
          setSelectedKurikulum(activeKurikulum.id_kurikulum);
        } else if (response.data.length > 0) {
          setSelectedKurikulum(response.data[0].id_kurikulum);
        }
      }
    } catch (error: any) {
      toast.error('Failed to load kurikulum');
      console.error(error);
    }
  };

  const loadCPLs = async () => {
    try {
      setIsLoading(true);
      const response = await cplService.getAll({
        id_kurikulum: selectedKurikulum || undefined
      });
      if (response.data && Array.isArray(response.data)) {
        setCpls(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load CPL');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (cpl: CPL) => {
    const confirmed = await confirm({
      type: 'danger',
      title: 'Delete CPL',
      message: `Are you sure you want to delete CPL "${cpl.kode_cpl}"? This action cannot be undone.`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
    });

    if (confirmed) {
      try {
        await cplService.delete(cpl.id_cpl);
        toast.success('CPL deleted successfully');
        loadCPLs();
      } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to delete CPL');
      }
    }
  };

  const handleEdit = (cpl: CPL) => {
    setEditingCPL(cpl);
    setShowForm(true);
  };

  const handleAddNew = () => {
    setEditingCPL(null);
    setShowForm(true);
  };

  const handleFormClose = () => {
    setShowForm(false);
    setEditingCPL(null);
    loadCPLs();
  };

  const getCategoryBadge = (kategori?: string) => {
    if (!kategori) return null;

    const styles: Record<string, string> = {
      sikap: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
      pengetahuan: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
      keterampilan_umum: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
      keterampilan_khusus: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    };

    return (
      <span
        className={`px-2 py-1 text-xs font-medium rounded-full ${
          styles[kategori] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
        }`}
      >
        {kategori.replace('_', ' ').toUpperCase()}
      </span>
    );
  };

  if (isLoading && cpls.length === 0) {
    return <SkeletonLoader variant="table" />;
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            CPL (Capaian Pembelajaran Lulusan)
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage graduate learning outcomes
          </p>
        </div>
        <button
          onClick={handleAddNew}
          disabled={!selectedKurikulum}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <FiPlus className="mr-2" />
          Add CPL
        </button>
      </div>

      {/* Filter Section */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div className="flex items-center gap-4">
          <FiFilter className="text-gray-500 dark:text-gray-400" />
          <div className="flex-1">
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Select Kurikulum
            </label>
            <select
              value={selectedKurikulum || ''}
              onChange={(e) => setSelectedKurikulum(Number(e.target.value))}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Select a kurikulum...</option>
              {kurikulums.map((k) => (
                <option key={k.id_kurikulum} value={k.id_kurikulum}>
                  {k.kode_kurikulum} - {k.nama_kurikulum} ({k.tahun_berlaku})
                  {k.status === 'active' && ' - ACTIVE'}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Table */}
      {!selectedKurikulum ? (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
          <FiFilter className="mx-auto text-gray-400 text-4xl mb-4" />
          <p className="text-gray-500 dark:text-gray-400">
            Please select a kurikulum to view CPL
          </p>
        </div>
      ) : (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead className="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Code
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Description
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Category
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
                  <td colSpan={5} className="px-6 py-4">
                    <SkeletonLoader variant="list" />
                  </td>
                </tr>
              ) : cpls.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No CPL found. Click "Add CPL" to create one.
                  </td>
                </tr>
              ) : (
                cpls.map((cpl) => (
                  <tr key={cpl.id_cpl} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {cpl.kode_cpl}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                      {cpl.deskripsi}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getCategoryBadge(cpl.kategori)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 py-1 text-xs font-medium rounded-full ${
                          cpl.is_active
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                        }`}
                      >
                        {cpl.is_active ? 'ACTIVE' : 'INACTIVE'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => handleEdit(cpl)}
                          className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                          title="Edit"
                        >
                          <FiEdit className="text-lg" />
                        </button>
                        <button
                          onClick={() => handleDelete(cpl)}
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
      )}

      {/* Form Modal */}
      {showForm && (
        <CPLFormModal
          cpl={editingCPL}
          kurikulumId={selectedKurikulum!}
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

// CPL Form Modal Component
interface CPLFormModalProps {
  cpl: CPL | null;
  kurikulumId: number;
  onClose: () => void;
}

const CPLFormModal: React.FC<CPLFormModalProps> = ({ cpl, kurikulumId, onClose }) => {
  const [formData, setFormData] = useState({
    kode_cpl: cpl?.kode_cpl || '',
    deskripsi: cpl?.deskripsi || '',
    kategori: cpl?.kategori || 'pengetahuan',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (cpl) {
        await cplService.update(cpl.id_cpl, formData);
        toast.success('CPL updated successfully');
      } else {
        await cplService.create({
          ...formData,
          id_kurikulum: kurikulumId,
        });
        toast.success('CPL created successfully');
      }
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to save CPL');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            {cpl ? 'Edit CPL' : 'Add New CPL'}
          </h2>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              CPL Code <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={formData.kode_cpl}
              onChange={(e) => setFormData({ ...formData, kode_cpl: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="e.g., CPL-01"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Description <span className="text-red-500">*</span>
            </label>
            <textarea
              value={formData.deskripsi}
              onChange={(e) => setFormData({ ...formData, deskripsi: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              rows={4}
              placeholder="Enter CPL description..."
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Category
            </label>
            <select
              value={formData.kategori}
              onChange={(e) => setFormData({ ...formData, kategori: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="sikap">Sikap</option>
              <option value="pengetahuan">Pengetahuan</option>
              <option value="keterampilan_umum">Keterampilan Umum</option>
              <option value="keterampilan_khusus">Keterampilan Khusus</option>
            </select>
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
              {isSubmitting ? 'Saving...' : cpl ? 'Update CPL' : 'Create CPL'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
