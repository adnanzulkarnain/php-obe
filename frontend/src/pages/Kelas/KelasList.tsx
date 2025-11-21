import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import {
  FiPlus,
  FiEdit2,
  FiTrash2,
  FiUsers,
  FiBook,
  FiFilter,
} from 'react-icons/fi';
import kelasService, { type Kelas, type CreateKelasDTO } from '../../services/kelas.service';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog } from '../../components/ConfirmDialog';

export const KelasList: React.FC = () => {
  const queryClient = useQueryClient();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [selectedKelas, setSelectedKelas] = useState<Kelas | null>(null);
  const [filters, setFilters] = useState<{
    tahun_ajaran: string;
    semester?: number;
    status_kelas: string;
  }>({
    tahun_ajaran: new Date().getFullYear().toString(),
    semester: undefined,
    status_kelas: '',
  });
  const [showFilters, setShowFilters] = useState(false);

  // Form state
  const [formData, setFormData] = useState<Partial<CreateKelasDTO>>({
    id_kurikulum: undefined,
    kode_mk: '',
    nama_kelas: '',
    semester: 1,
    tahun_ajaran: new Date().getFullYear().toString(),
    kapasitas: 40,
  });

  // Fetch kelas list
  const {
    data: kelasList,
    isLoading,
    error,
  } = useQuery<Kelas[]>({
    queryKey: ['kelas', filters],
    queryFn: () => kelasService.getAll(filters),
  });

  // TODO: Add kurikulum and matakuliah services when available
  // For now, using manual input

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: CreateKelasDTO) => kelasService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kelas'] });
      toast.success('Kelas berhasil ditambahkan');
      setIsModalOpen(false);
      resetForm();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal menambahkan kelas');
    },
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateKelasDTO> }) =>
      kelasService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kelas'] });
      toast.success('Kelas berhasil diupdate');
      setIsModalOpen(false);
      setSelectedKelas(null);
      resetForm();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal mengupdate kelas');
    },
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => kelasService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kelas'] });
      toast.success('Kelas berhasil dihapus');
      setIsDeleteDialogOpen(false);
      setSelectedKelas(null);
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal menghapus kelas');
    },
  });

  // Status change mutation
  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      kelasService.changeStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kelas'] });
      toast.success('Status kelas berhasil diubah');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal mengubah status kelas');
    },
  });

  const resetForm = () => {
    setFormData({
      id_kurikulum: undefined,
      kode_mk: '',
      nama_kelas: '',
      semester: 1,
      tahun_ajaran: new Date().getFullYear().toString(),
      kapasitas: 40,
    });
  };

  const handleOpenModal = (kelas?: Kelas) => {
    if (kelas) {
      setSelectedKelas(kelas);
      setFormData({
        id_kurikulum: kelas.id_kurikulum,
        kode_mk: kelas.kode_mk,
        nama_kelas: kelas.nama_kelas,
        semester: kelas.semester,
        tahun_ajaran: kelas.tahun_ajaran,
        kapasitas: kelas.kapasitas,
        id_rps: kelas.id_rps,
      });
    } else {
      setSelectedKelas(null);
      resetForm();
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (selectedKelas) {
      updateMutation.mutate({
        id: selectedKelas.id_kelas,
        data: formData as CreateKelasDTO,
      });
    } else {
      createMutation.mutate(formData as CreateKelasDTO);
    }
  };

  const handleDelete = () => {
    if (selectedKelas) {
      deleteMutation.mutate(selectedKelas.id_kelas);
    }
  };

  const handleStatusChange = (kelas: Kelas, newStatus: string) => {
    statusMutation.mutate({ id: kelas.id_kelas, status: newStatus });
  };

  const getStatusBadge = (status: string) => {
    const badges: Record<string, { color: string; label: string }> = {
      draft: { color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', label: 'Draft' },
      buka: { color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', label: 'Buka' },
      berlangsung: { color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', label: 'Berlangsung' },
      selesai: { color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300', label: 'Selesai' },
      batal: { color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', label: 'Batal' },
    };
    const badge = badges[status] || badges.draft;
    return (
      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${badge.color}`}>
        {badge.label}
      </span>
    );
  };

  if (error) {
    return (
      <div className="text-center py-12">
        <p className="text-red-600 dark:text-red-400">
          Error loading kelas: {(error as Error).message}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Kelas</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-2">
            Kelola kelas dan tugas mengajar dosen
          </p>
        </div>
        <button
          onClick={() => handleOpenModal()}
          className="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors"
        >
          <FiPlus />
          Tambah Kelas
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <button
          onClick={() => setShowFilters(!showFilters)}
          className="flex items-center gap-2 text-gray-700 dark:text-gray-300 font-medium mb-4"
        >
          <FiFilter />
          {showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter'}
        </button>

        {showFilters && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Tahun Ajaran
              </label>
              <input
                type="text"
                value={filters.tahun_ajaran}
                onChange={(e) => setFilters({ ...filters, tahun_ajaran: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                placeholder="2024"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Semester
              </label>
              <select
                value={filters.semester || ''}
                onChange={(e) => setFilters({ ...filters, semester: e.target.value ? Number(e.target.value) : undefined })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Semua Semester</option>
                {[1, 2, 3, 4, 5, 6, 7, 8].map((sem) => (
                  <option key={sem} value={sem}>
                    Semester {sem}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Status
              </label>
              <select
                value={filters.status_kelas}
                onChange={(e) => setFilters({ ...filters, status_kelas: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="buka">Buka</option>
                <option value="berlangsung">Berlangsung</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Batal</option>
              </select>
            </div>
          </div>
        )}
      </div>

      {/* Kelas List */}
      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <SkeletonLoader key={i} className="h-64" />
          ))}
        </div>
      ) : kelasList && kelasList.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {kelasList.map((kelas) => (
            <div
              key={kelas.id_kelas}
              className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex justify-between items-start mb-4">
                <div className="flex-1">
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                    {kelas.nama_kelas}
                  </h3>
                  <p className="text-sm text-gray-600 dark:text-gray-400">{kelas.nama_mk}</p>
                </div>
                {getStatusBadge(kelas.status_kelas)}
              </div>

              <div className="space-y-2 mb-4">
                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <FiBook className="flex-shrink-0" />
                  <span>
                    Semester {kelas.semester} - {kelas.tahun_ajaran}
                  </span>
                </div>
                <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <FiUsers className="flex-shrink-0" />
                  <span>
                    {kelas.jumlah_mahasiswa || 0} / {kelas.kapasitas} mahasiswa
                  </span>
                </div>
              </div>

              {/* Status Actions */}
              <div className="flex gap-2 mb-4">
                {kelas.status_kelas === 'draft' && (
                  <button
                    onClick={() => handleStatusChange(kelas, 'buka')}
                    className="flex-1 text-xs px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800"
                  >
                    Buka Kelas
                  </button>
                )}
                {kelas.status_kelas === 'buka' && (
                  <button
                    onClick={() => handleStatusChange(kelas, 'berlangsung')}
                    className="flex-1 text-xs px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded hover:bg-green-200 dark:hover:bg-green-800"
                  >
                    Mulai Kelas
                  </button>
                )}
                {kelas.status_kelas === 'berlangsung' && (
                  <button
                    onClick={() => handleStatusChange(kelas, 'selesai')}
                    className="flex-1 text-xs px-2 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 rounded hover:bg-purple-200 dark:hover:bg-purple-800"
                  >
                    Selesai
                  </button>
                )}
              </div>

              {/* Actions */}
              <div className="flex gap-2">
                <button
                  onClick={() => handleOpenModal(kelas)}
                  className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                  <FiEdit2 size={16} />
                  Edit
                </button>
                <button
                  onClick={() => {
                    setSelectedKelas(kelas);
                    setIsDeleteDialogOpen(true);
                  }}
                  className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50"
                >
                  <FiTrash2 size={16} />
                  Hapus
                </button>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <p className="text-gray-500 dark:text-gray-400">
            Tidak ada kelas ditemukan. Klik "Tambah Kelas" untuk membuat kelas baru.
          </p>
        </div>
      )}

      {/* Create/Edit Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {selectedKelas ? 'Edit Kelas' : 'Tambah Kelas'}
              </h2>

              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      ID Kurikulum <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="number"
                      value={formData.id_kurikulum || ''}
                      onChange={(e) =>
                        setFormData({ ...formData, id_kurikulum: Number(e.target.value) })
                      }
                      required
                      disabled={!!selectedKelas}
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50"
                      placeholder="1"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Kode Mata Kuliah <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={formData.kode_mk || ''}
                      onChange={(e) => setFormData({ ...formData, kode_mk: e.target.value })}
                      required
                      disabled={!!selectedKelas}
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white disabled:opacity-50"
                      placeholder="MK001"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nama Kelas <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.nama_kelas}
                    onChange={(e) => setFormData({ ...formData, nama_kelas: e.target.value })}
                    required
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    placeholder="Kelas A, Kelas B, dll"
                  />
                </div>

                <div className="grid grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Semester <span className="text-red-500">*</span>
                    </label>
                    <select
                      value={formData.semester}
                      onChange={(e) =>
                        setFormData({ ...formData, semester: Number(e.target.value) })
                      }
                      required
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    >
                      {[1, 2, 3, 4, 5, 6, 7, 8].map((sem) => (
                        <option key={sem} value={sem}>
                          Semester {sem}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Tahun Ajaran <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={formData.tahun_ajaran}
                      onChange={(e) => setFormData({ ...formData, tahun_ajaran: e.target.value })}
                      required
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                      placeholder="2024"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Kapasitas <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="number"
                      value={formData.kapasitas}
                      onChange={(e) =>
                        setFormData({ ...formData, kapasitas: Number(e.target.value) })
                      }
                      required
                      min="1"
                      className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    />
                  </div>
                </div>

                <div className="flex gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => {
                      setIsModalOpen(false);
                      setSelectedKelas(null);
                      resetForm();
                    }}
                    className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={createMutation.isPending || updateMutation.isPending}
                    className="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                  >
                    {createMutation.isPending || updateMutation.isPending
                      ? 'Menyimpan...'
                      : 'Simpan'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={isDeleteDialogOpen}
        onCancel={() => {
          setIsDeleteDialogOpen(false);
          setSelectedKelas(null);
        }}
        onConfirm={handleDelete}
        title="Hapus Kelas"
        message={`Apakah Anda yakin ingin menghapus kelas "${selectedKelas?.nama_kelas}"? Tindakan ini tidak dapat dibatalkan.`}
        confirmText="Hapus"
        confirmButtonClass="bg-red-600 hover:bg-red-700"
      />
    </div>
  );
};
