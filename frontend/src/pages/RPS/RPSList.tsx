import { useState, useEffect } from 'react';
import { rpsService, type RPS } from '../../services/rps.service';
import { kurikulumService } from '../../services/kurikulum.service';
import { mataKuliahService } from '../../services/matakuliah.service';
import { dosenService } from '../../services/dosen.service';
import type { Kurikulum, MataKuliah } from '../../types/api';
import type { Dosen } from '../../services/dosen.service';
import { toast } from 'react-toastify';
import { FiPlus, FiEdit, FiTrash2, FiFilter, FiFileText, FiSend, FiCheckCircle, FiArchive } from 'react-icons/fi';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog, useConfirmDialog } from '../../components/ConfirmDialog';
import { RPSWizard } from './RPSWizard';

export const RPSList: React.FC = () => {
  const [rpsList, setRpsList] = useState<RPS[]>([]);
  const [kurikulums, setKurikulums] = useState<Kurikulum[]>([]);
  const [mataKuliahs, setMataKuliahs] = useState<MataKuliah[]>([]);
  const [dosens, setDosens] = useState<Dosen[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filterKurikulum, setFilterKurikulum] = useState<number | null>(null);
  const [filterStatus, setFilterStatus] = useState<string>('');
  const [showForm, setShowForm] = useState(false);
  const [editingRPS, setEditingRPS] = useState<RPS | null>(null);
  const { isOpen, config, confirm, closeDialog } = useConfirmDialog();

  useEffect(() => {
    loadKurikulums();
    loadDosens();
    loadRPSList();
  }, []);

  useEffect(() => {
    if (filterKurikulum) {
      loadMataKuliahs();
    }
  }, [filterKurikulum]);

  useEffect(() => {
    loadRPSList();
  }, [filterKurikulum, filterStatus]);

  const loadKurikulums = async () => {
    try {
      const response = await kurikulumService.getAll();
      if (response.data && Array.isArray(response.data)) {
        setKurikulums(response.data);
        const active = response.data.find(k => k.status === 'active');
        if (active) setFilterKurikulum(active.id_kurikulum);
      }
    } catch (error: any) {
      toast.error('Failed to load kurikulum');
    }
  };

  const loadMataKuliahs = async () => {
    try {
      const response = await mataKuliahService.getAll({
        id_kurikulum: filterKurikulum || undefined
      });
      if (response.data && Array.isArray(response.data)) {
        setMataKuliahs(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load mata kuliah');
    }
  };

  const loadDosens = async () => {
    try {
      const response = await dosenService.getAll({ status: 'aktif' });
      if (response.data && Array.isArray(response.data)) {
        setDosens(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load dosen');
    }
  };

  const loadRPSList = async () => {
    try {
      setIsLoading(true);
      const params: any = {};
      if (filterKurikulum) params.id_kurikulum = filterKurikulum;
      if (filterStatus) params.status = filterStatus;

      const response = await rpsService.getAll(params);
      if (response.data && Array.isArray(response.data)) {
        setRpsList(response.data);
      }
    } catch (error: any) {
      toast.error('Failed to load RPS');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (rps: RPS) => {
    const confirmed = await confirm({
      type: 'danger',
      title: 'Delete RPS',
      message: `Are you sure you want to delete RPS for ${rps.kode_mk}? This action cannot be undone.`,
      confirmText: 'Delete',
      cancelText: 'Cancel',
    });

    if (confirmed) {
      try {
        await rpsService.delete(rps.id_rps);
        toast.success('RPS deleted successfully');
        loadRPSList();
      } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to delete RPS');
      }
    }
  };

  const handleSubmit = async (rps: RPS) => {
    try {
      await rpsService.submit(rps.id_rps);
      toast.success('RPS submitted for approval');
      loadRPSList();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to submit RPS');
    }
  };

  const handleActivate = async (rps: RPS) => {
    try {
      await rpsService.activate(rps.id_rps);
      toast.success('RPS activated successfully');
      loadRPSList();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to activate RPS');
    }
  };

  const getStatusBadge = (status: string) => {
    const styles: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
      submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
      approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
      active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
      archived: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    };

    return (
      <span className={`px-2 py-1 text-xs font-medium rounded-full ${styles[status] || styles.draft}`}>
        {status.toUpperCase()}
      </span>
    );
  };

  const getMKName = (kode_mk: string) => {
    const mk = mataKuliahs.find(m => m.kode_mk === kode_mk);
    return mk ? mk.nama_mk : kode_mk;
  };

  if (isLoading && rpsList.length === 0) {
    return <SkeletonLoader variant="table" />;
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            RPS (Rencana Pembelajaran Semester)
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage semester learning plans
          </p>
        </div>
        <button
          onClick={() => { setEditingRPS(null); setShowForm(true); }}
          disabled={!filterKurikulum}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <FiPlus className="mr-2" />
          Add RPS
        </button>
      </div>

      {/* Filter Section */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div className="flex items-start gap-4">
          <FiFilter className="text-gray-500 dark:text-gray-400 mt-2" />
          <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Kurikulum
              </label>
              <select
                value={filterKurikulum || ''}
                onChange={(e) => setFilterKurikulum(Number(e.target.value))}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">Select Kurikulum</option>
                {kurikulums.map((k) => (
                  <option key={k.id_kurikulum} value={k.id_kurikulum}>
                    {k.kode_kurikulum} - {k.nama_kurikulum} ({k.tahun_berlaku})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Status
              </label>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiFileText className="text-3xl text-primary-600 dark:text-primary-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Total</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{rpsList.length}</p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiSend className="text-3xl text-blue-600 dark:text-blue-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Submitted</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {rpsList.filter(r => r.status === 'submitted').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiCheckCircle className="text-3xl text-green-600 dark:text-green-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Active</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {rpsList.filter(r => r.status === 'active').length}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <FiArchive className="text-3xl text-orange-600 dark:text-orange-400 mr-3" />
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Archived</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {rpsList.filter(r => r.status === 'archived').length}
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
                  Mata Kuliah
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Dosen
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  TA / Semester
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Version
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              {isLoading ? (
                <tr><td colSpan={6} className="px-6 py-4"><SkeletonLoader variant="list" /></td></tr>
              ) : rpsList.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No RPS found. Click "Add RPS" to create one.
                  </td>
                </tr>
              ) : (
                rpsList.map((rps) => (
                  <tr key={rps.id_rps} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">
                      <div className="font-medium">{rps.kode_mk}</div>
                      <div className="text-xs text-gray-500 dark:text-gray-400">{getMKName(rps.kode_mk)}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {rps.nama_ketua || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {rps.tahun_ajaran} / {rps.semester_berlaku}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                      {rps.current_version || 'v1'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(rps.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                      <div className="flex items-center space-x-2">
                        {rps.status === 'draft' && (
                          <button
                            onClick={() => handleSubmit(rps)}
                            className="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                            title="Submit for Approval"
                          >
                            <FiSend className="text-lg" />
                          </button>
                        )}
                        {rps.status === 'approved' && (
                          <button
                            onClick={() => handleActivate(rps)}
                            className="text-green-600 hover:text-green-900 dark:text-green-400"
                            title="Activate"
                          >
                            <FiCheckCircle className="text-lg" />
                          </button>
                        )}
                        <button
                          onClick={() => { setEditingRPS(rps); setShowForm(true); }}
                          className="text-green-600 hover:text-green-900 dark:text-green-400"
                          title="Edit"
                        >
                          <FiEdit className="text-lg" />
                        </button>
                        {rps.status === 'draft' && (
                          <button
                            onClick={() => handleDelete(rps)}
                            className="text-red-600 hover:text-red-900 dark:text-red-400"
                            title="Delete"
                          >
                            <FiTrash2 className="text-lg" />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {showForm && !editingRPS && (
        <RPSWizard
          kurikulumId={filterKurikulum!}
          kurikulums={kurikulums}
          mataKuliahs={mataKuliahs}
          dosens={dosens}
          onClose={() => { setShowForm(false); }}
          onSuccess={() => { loadRPSList(); }}
        />
      )}

      {showForm && editingRPS && (
        <RPSFormModal
          rps={editingRPS}
          kurikulumId={filterKurikulum!}
          mataKuliahs={mataKuliahs}
          dosens={dosens}
          onClose={() => { setShowForm(false); setEditingRPS(null); loadRPSList(); }}
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

// RPS Form Modal
interface RPSFormModalProps {
  rps: RPS | null;
  kurikulumId: number;
  mataKuliahs: MataKuliah[];
  dosens: Dosen[];
  onClose: () => void;
}

const RPSFormModal: React.FC<RPSFormModalProps> = ({ rps, onClose }) => {
  const [formData, setFormData] = useState({
    deskripsi_mk: rps?.deskripsi_mk || '',
    deskripsi_singkat: rps?.deskripsi_singkat || '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (rps) {
        await rpsService.update(rps.id_rps, formData);
        toast.success('RPS updated successfully');
      }
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to save RPS');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            {rps ? 'Edit RPS' : 'Add New RPS'}
          </h2>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div className="space-y-4">
            <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <p className="text-sm text-gray-600 dark:text-gray-400">
                <strong>Mata Kuliah:</strong> {rps?.kode_mk} - {rps?.nama_mk}
              </p>
              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                <strong>Semester:</strong> {rps?.semester_berlaku} | <strong>Tahun Ajaran:</strong> {rps?.tahun_ajaran}
              </p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Deskripsi Mata Kuliah
              </label>
              <textarea
                value={formData.deskripsi_mk}
                onChange={(e) => setFormData({ ...formData, deskripsi_mk: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                rows={6}
                placeholder="Enter course description..."
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Deskripsi Singkat
              </label>
              <textarea
                value={formData.deskripsi_singkat}
                onChange={(e) => setFormData({ ...formData, deskripsi_singkat: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                rows={3}
                placeholder="Enter brief description..."
              />
            </div>
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
              {isSubmitting ? 'Saving...' : rps ? 'Update RPS' : 'Create RPS'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
