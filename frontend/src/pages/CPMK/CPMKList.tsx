import { useState, useEffect } from 'react';
import { cpmkService } from '../../services/cpmk.service';
import { kurikulumService } from '../../services/kurikulum.service';
import { mataKuliahService } from '../../services/matakuliah.service';
import type { CPMK, Kurikulum, MataKuliah } from '../../types/api';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiFilter, FiLayers } from 'react-icons/fi';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog, useConfirmDialog } from '../../components/ConfirmDialog';

export const CPMKList: React.FC = () => {
  const [cpmks, setCpmks] = useState<CPMK[]>([]);
  const [kurikulums, setKurikulums] = useState<Kurikulum[]>([]);
  const [mataKuliahs, setMataKuliahs] = useState<MataKuliah[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedKurikulum, setSelectedKurikulum] = useState<number | null>(null);
  const [selectedMK, setSelectedMK] = useState<string>('');
  const [showForm, setShowForm] = useState(false);
  const [editingCPMK, setEditingCPMK] = useState<CPMK | null>(null);
  const { isOpen, config, confirm, closeDialog } = useConfirmDialog();

  useEffect(() => {
    loadKurikulums();
  }, []);

  useEffect(() => {
    if (selectedKurikulum) {
      loadMataKuliahs();
    }
  }, [selectedKurikulum]);

  useEffect(() => {
    if (selectedMK && selectedKurikulum) {
      loadCPMKs();
    }
  }, [selectedMK, selectedKurikulum]);

  const loadKurikulums = async () => {
    try {
      const response = await kurikulumService.getAll();
      if (response.data) {
        setKurikulums(response.data);
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

  const loadMataKuliahs = async () => {
    try {
      const response = await mataKuliahService.getAll({
        id_kurikulum: selectedKurikulum || undefined
      });
      if (response.data && Array.isArray(response.data)) {
        setMataKuliahs(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load mata kuliah');
      console.error(error);
    }
  };

  const loadCPMKs = async () => {
    try {
      setIsLoading(true);
      const response = await cpmkService.getAll({
        kode_mk: selectedMK || undefined,
        id_kurikulum: selectedKurikulum || undefined
      });
      if (response.data && Array.isArray(response.data)) {
        setCpmks(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load CPMK');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (cpmk: CPMK) => {
    const confirmed = await confirm({
      type: 'danger',
      title: 'Delete CPMK',
      message: `Are you sure you want to delete CPMK "${cpmk.kode_cpmk}"? This action cannot be undone.`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
    });

    if (confirmed) {
      try {
        await cpmkService.delete(cpmk.id_cpmk);
        toast.success('CPMK deleted successfully');
        loadCPMKs();
      } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to delete CPMK');
      }
    }
  };

  const handleEdit = (cpmk: CPMK) => {
    setEditingCPMK(cpmk);
    setShowForm(true);
  };

  const handleAddNew = () => {
    setEditingCPMK(null);
    setShowForm(true);
  };

  const handleFormClose = () => {
    setShowForm(false);
    setEditingCPMK(null);
    loadCPMKs();
  };

  if (isLoading && cpmks.length === 0) {
    return <SkeletonLoader variant="table" />;
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            CPMK (Capaian Pembelajaran Mata Kuliah)
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage course learning outcomes
          </p>
        </div>
        <button
          onClick={handleAddNew}
          disabled={!selectedMK}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <FiPlus className="mr-2" />
          Add CPMK
        </button>
      </div>

      {/* Filter Section */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div className="flex items-start gap-4">
          <FiFilter className="text-gray-500 dark:text-gray-400 mt-2" />
          <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Select Kurikulum
              </label>
              <select
                value={selectedKurikulum || ''}
                onChange={(e) => {
                  setSelectedKurikulum(Number(e.target.value));
                  setSelectedMK('');
                }}
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

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Select Mata Kuliah
              </label>
              <select
                value={selectedMK}
                onChange={(e) => setSelectedMK(e.target.value)}
                disabled={!selectedKurikulum}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option value="">Select a mata kuliah...</option>
                {mataKuliahs.map((mk) => (
                  <option key={mk.kode_mk} value={mk.kode_mk}>
                    {mk.kode_mk} - {mk.nama_mk} ({mk.sks} SKS, Sem {mk.semester})
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Table */}
      {!selectedMK ? (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
          <FiLayers className="mx-auto text-gray-400 text-4xl mb-4" />
          <p className="text-gray-500 dark:text-gray-400">
            Please select a kurikulum and mata kuliah to view CPMK
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
                  Mata Kuliah
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
              ) : cpmks.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No CPMK found. Click "Add CPMK" to create one.
                  </td>
                </tr>
              ) : (
                cpmks.map((cpmk) => (
                  <tr key={cpmk.id_cpmk} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {cpmk.kode_cpmk}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                      {cpmk.deskripsi}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {cpmk.kode_mk}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 py-1 text-xs font-medium rounded-full ${
                          cpmk.is_active
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                        }`}
                      >
                        {cpmk.is_active ? 'ACTIVE' : 'INACTIVE'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => handleEdit(cpmk)}
                          className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                          title="Edit"
                        >
                          <FiEdit className="text-lg" />
                        </button>
                        <button
                          onClick={() => handleDelete(cpmk)}
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
        <CPMKFormModal
          cpmk={editingCPMK}
          kodeMK={selectedMK}
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

// CPMK Form Modal Component
interface CPMKFormModalProps {
  cpmk: CPMK | null;
  kodeMK: string;
  kurikulumId: number;
  onClose: () => void;
}

const CPMKFormModal: React.FC<CPMKFormModalProps> = ({ cpmk, kodeMK, kurikulumId, onClose }) => {
  const [formData, setFormData] = useState({
    kode_cpmk: cpmk?.kode_cpmk || '',
    deskripsi: cpmk?.deskripsi || '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (cpmk) {
        await cpmkService.update(cpmk.id_cpmk, formData);
        toast.success('CPMK updated successfully');
      } else {
        await cpmkService.create({
          ...formData,
          kode_mk: kodeMK,
          id_kurikulum: kurikulumId,
        });
        toast.success('CPMK created successfully');
      }
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to save CPMK');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            {cpmk ? 'Edit CPMK' : 'Add New CPMK'}
          </h2>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Mata Kuliah: {kodeMK}
          </p>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              CPMK Code <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={formData.kode_cpmk}
              onChange={(e) => setFormData({ ...formData, kode_cpmk: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="e.g., CPMK-01"
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
              placeholder="Enter CPMK description..."
              required
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
              {isSubmitting ? 'Saving...' : cpmk ? 'Update CPMK' : 'Create CPMK'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
