import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tantml:invoke>
<parameter name="content">import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import { FiSave, FiSend, FiArrowLeft, FiAlertCircle } from 'react-icons/fi';
import { realisasiPertemuanService } from '../../services/realisasi-pertemuan.service';
import { kelasService } from '../../services/kelas.service';
import type { RealisasiPertemuan, Kehadiran } from '../../types/api';
import { KehadiranInput } from '../../components/KehadiranInput';
import { useAuth } from '../../hooks/useAuth';

interface FormData {
  id_kelas: number | '';
  id_minggu?: number;
  tanggal_pelaksanaan: string;
  materi_disampaikan: string;
  metode_digunakan: string;
  kendala: string;
  catatan_dosen: string;
  kehadiran: Kehadiran[];
}

export const RealisasiPertemuanForm: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user } = useAuth();
  const isEditMode = !!id;

  const [formData, setFormData] = useState<FormData>({
    id_kelas: '',
    tanggal_pelaksanaan: new Date().toISOString().split('T')[0],
    materi_disampaikan: '',
    metode_digunakan: '',
    kendala: '',
    catatan_dosen: '',
    kehadiran: [],
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  // Fetch dosen's classes
  const { data: kelasList } = useQuery({
    queryKey: ['dosen-kelas', user?.ref_id],
    queryFn: () => kelasService.getByDosen(user?.ref_id || ''),
    select: (response) => response.data?.data || [],
    enabled: !!user?.ref_id,
  });

  // Fetch existing data if edit mode
  const { data: existingData, isLoading: isLoadingData } = useQuery({
    queryKey: ['realisasi-pertemuan', id],
    queryFn: () => realisasiPertemuanService.getById(Number(id)),
    select: (response) => response.data?.data,
    enabled: isEditMode,
  });

  // Fetch mahasiswa list for selected kelas
  const { data: mahasiswaList } = useQuery({
    queryKey: ['kelas-mahasiswa', formData.id_kelas],
    queryFn: () => kelasService.getEnrollment(Number(formData.id_kelas)),
    select: (response) => response.data?.data || [],
    enabled: !!formData.id_kelas && formData.id_kelas !== '',
  });

  // Populate form if edit mode
  useEffect(() => {
    if (existingData) {
      setFormData({
        id_kelas: existingData.id_kelas,
        id_minggu: existingData.id_minggu,
        tanggal_pelaksanaan: existingData.tanggal_pelaksanaan,
        materi_disampaikan: existingData.materi_disampaikan || '',
        metode_digunakan: existingData.metode_digunakan || '',
        kendala: existingData.kendala || '',
        catatan_dosen: existingData.catatan_dosen || '',
        kehadiran: existingData.kehadiran || [],
      });
    }
  }, [existingData]);

  // Initialize kehadiran when mahasiswa list loads
  useEffect(() => {
    if (mahasiswaList && mahasiswaList.length > 0 && !isEditMode) {
      setFormData((prev) => ({
        ...prev,
        kehadiran: mahasiswaList.map((mhs: any) => ({
          id_realisasi: 0,
          nim: mhs.nim,
          nama_mahasiswa: mhs.nama,
          status: 'hadir' as const,
          keterangan: '',
        })),
      }));
    }
  }, [mahasiswaList, isEditMode]);

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: any) => realisasiPertemuanService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['realisasi-pertemuan'] });
      toast.success('Berita acara berhasil disimpan');
      navigate('/berita-acara');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal menyimpan berita acara');
    },
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: (data: any) => realisasiPertemuanService.update(Number(id), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['realisasi-pertemuan'] });
      toast.success('Berita acara berhasil diperbarui');
      navigate('/berita-acara');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal memperbarui berita acara');
    },
  });

  // Submit mutation
  const submitMutation = useMutation({
    mutationFn: (idRealisasi: number) => realisasiPertemuanService.submit(idRealisasi),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['realisasi-pertemuan'] });
      toast.success('Berita acara berhasil disubmit untuk verifikasi');
      navigate('/berita-acara');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal submit berita acara');
    },
  });

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.id_kelas) {
      newErrors.id_kelas = 'Kelas harus dipilih';
    }
    if (!formData.tanggal_pelaksanaan) {
      newErrors.tanggal_pelaksanaan = 'Tanggal pelaksanaan harus diisi';
    }
    if (!formData.materi_disampaikan.trim()) {
      newErrors.materi_disampaikan = 'Materi yang disampaikan harus diisi';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSaveDraft = (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      toast.error('Mohon lengkapi form yang wajib diisi');
      return;
    }

    const data = {
      ...formData,
      id_kelas: Number(formData.id_kelas),
    };

    if (isEditMode) {
      updateMutation.mutate(data);
    } else {
      createMutation.mutate(data);
    }
  };

  const handleSubmitForVerification = async () => {
    if (!validate()) {
      toast.error('Mohon lengkapi form yang wajib diisi');
      return;
    }

    try {
      // Save first if creating new
      if (!isEditMode) {
        const response = await realisasiPertemuanService.create({
          ...formData,
          id_kelas: Number(formData.id_kelas),
        });
        const createdId = response.data?.data?.id_realisasi;
        if (createdId) {
          submitMutation.mutate(createdId);
        }
      } else {
        // Update then submit
        await realisasiPertemuanService.update(Number(id), {
          ...formData,
          id_kelas: Number(formData.id_kelas),
        });
        submitMutation.mutate(Number(id));
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Gagal menyimpan berita acara');
    }
  };

  const handleKehadiranChange = (updatedKehadiran: Kehadiran[]) => {
    setFormData((prev) => ({
      ...prev,
      kehadiran: updatedKehadiran,
    }));
  };

  if (isEditMode && isLoadingData) {
    return <div className="p-6">Loading...</div>;
  }

  // Check if can edit
  const canEdit =
    !isEditMode ||
    existingData?.status === 'draft' ||
    existingData?.status === 'rejected';

  if (isEditMode && !canEdit) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
          <FiAlertCircle className="w-5 h-5 text-yellow-600 mt-0.5" />
          <div>
            <h3 className="font-medium text-yellow-900">
              Berita Acara Tidak Dapat Diedit
            </h3>
            <p className="text-sm text-yellow-700 mt-1">
              Berita acara dengan status "{existingData?.status}" tidak dapat diedit.
            </p>
            <button
              onClick={() => navigate('/berita-acara')}
              className="mt-3 text-sm text-yellow-700 hover:text-yellow-800 underline"
            >
              Kembali ke daftar
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <button
          onClick={() => navigate('/berita-acara')}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <FiArrowLeft className="w-5 h-5" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            {isEditMode ? 'Edit' : 'Buat'} Berita Acara Perkuliahan
          </h1>
          <p className="text-sm text-gray-600 mt-1">
            Lengkapi data berita acara dan kehadiran mahasiswa
          </p>
        </div>
      </div>

      {/* Rejection Notice */}
      {isEditMode && existingData?.status === 'rejected' && existingData.komentar_kaprodi && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <h3 className="font-medium text-red-900 mb-2">Komentar Kaprodi</h3>
          <p className="text-sm text-red-700">{existingData.komentar_kaprodi}</p>
        </div>
      )}

      {/* Form */}
      <form onSubmit={handleSaveDraft} className="space-y-6">
        {/* Basic Info */}
        <div className="bg-white rounded-lg shadow p-6 space-y-4">
          <h2 className="text-lg font-semibold text-gray-900">Informasi Dasar</h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Kelas <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.id_kelas}
                onChange={(e) =>
                  setFormData({ ...formData, id_kelas: Number(e.target.value) || '' })
                }
                className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                  errors.id_kelas ? 'border-red-500' : 'border-gray-300'
                }`}
                disabled={isEditMode}
              >
                <option value="">Pilih Kelas</option>
                {kelasList?.map((kelas: any) => (
                  <option key={kelas.id_kelas} value={kelas.id_kelas}>
                    {kelas.nama_mk} - Kelas {kelas.nama_kelas}
                  </option>
                ))}
              </select>
              {errors.id_kelas && (
                <p className="text-sm text-red-600 mt-1">{errors.id_kelas}</p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Tanggal Pelaksanaan <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                value={formData.tanggal_pelaksanaan}
                onChange={(e) =>
                  setFormData({ ...formData, tanggal_pelaksanaan: e.target.value })
                }
                max={new Date().toISOString().split('T')[0]}
                className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                  errors.tanggal_pelaksanaan ? 'border-red-500' : 'border-gray-300'
                }`}
              />
              {errors.tanggal_pelaksanaan && (
                <p className="text-sm text-red-600 mt-1">{errors.tanggal_pelaksanaan}</p>
              )}
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Materi yang Disampaikan <span className="text-red-500">*</span>
            </label>
            <textarea
              value={formData.materi_disampaikan}
              onChange={(e) =>
                setFormData({ ...formData, materi_disampaikan: e.target.value })
              }
              rows={4}
              placeholder="Jelaskan materi yang telah disampaikan pada pertemuan ini..."
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.materi_disampaikan ? 'border-red-500' : 'border-gray-300'
              }`}
            />
            {errors.materi_disampaikan && (
              <p className="text-sm text-red-600 mt-1">{errors.materi_disampaikan}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Metode Pembelajaran
            </label>
            <textarea
              value={formData.metode_digunakan}
              onChange={(e) =>
                setFormData({ ...formData, metode_digunakan: e.target.value })
              }
              rows={3}
              placeholder="Contoh: Ceramah, Diskusi, Praktik, dll."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Kendala (Jika Ada)
            </label>
            <textarea
              value={formData.kendala}
              onChange={(e) => setFormData({ ...formData, kendala: e.target.value })}
              rows={2}
              placeholder="Kendala atau hambatan selama perkuliahan (opsional)..."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Catatan Dosen
            </label>
            <textarea
              value={formData.catatan_dosen}
              onChange={(e) => setFormData({ ...formData, catatan_dosen: e.target.value })}
              rows={2}
              placeholder="Catatan tambahan (opsional)..."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        {/* Kehadiran Section */}
        {formData.id_kelas && (
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Kehadiran Mahasiswa
            </h2>
            <KehadiranInput
              kehadiran={formData.kehadiran}
              onChange={handleKehadiranChange}
            />
          </div>
        )}

        {/* Actions */}
        <div className="flex items-center justify-between bg-white rounded-lg shadow p-4">
          <button
            type="button"
            onClick={() => navigate('/berita-acara')}
            className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            Batal
          </button>

          <div className="flex items-center gap-3">
            <button
              type="submit"
              disabled={createMutation.isPending || updateMutation.isPending}
              className="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50"
            >
              <FiSave className="w-5 h-5" />
              Simpan Draft
            </button>

            <button
              type="button"
              onClick={handleSubmitForVerification}
              disabled={submitMutation.isPending}
              className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              <FiSend className="w-5 h-5" />
              Submit untuk Verifikasi
            </button>
          </div>
        </div>
      </form>
    </div>
  );
};
