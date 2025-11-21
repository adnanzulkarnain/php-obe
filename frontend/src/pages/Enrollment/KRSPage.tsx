import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import { FiPlus, FiTrash2, FiBook, FiUsers, FiCheckCircle, FiAlertCircle } from 'react-icons/fi';
import enrollmentService, { type Enrollment, type KRS } from '../../services/enrollment.service';
import kelasService, { type Kelas } from '../../services/kelas.service';
import { useAuth } from '../../contexts/AuthContext';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog } from '../../components/ConfirmDialog';

export const KRSPage: React.FC = () => {
  const queryClient = useQueryClient();
  const { user } = useAuth();
  const [selectedSemester, setSelectedSemester] = useState({
    semester: new Date().getMonth() < 6 ? 2 : 1, // Genap or Ganjil
    tahun_ajaran: new Date().getFullYear().toString(),
  });
  const [isDropDialogOpen, setIsDropDialogOpen] = useState(false);
  const [selectedEnrollment, setSelectedEnrollment] = useState<Enrollment | null>(null);
  const [showAvailableClasses, setShowAvailableClasses] = useState(false);

  // Get student's NIM (assuming it's stored in user object or we need to fetch it)
  const nim = user?.username || ''; // Adjust based on your auth context

  // Fetch KRS (current enrollments)
  const {
    data: krsData,
    isLoading: krsLoading,
    error: krsError,
  } = useQuery<KRS>({
    queryKey: ['krs', nim, selectedSemester],
    queryFn: () => enrollmentService.getKRS(nim, selectedSemester),
    enabled: !!nim,
  });

  // Fetch available classes for enrollment
  const {
    data: availableClasses,
    isLoading: classesLoading,
  } = useQuery<Kelas[]>({
    queryKey: ['available-kelas', selectedSemester],
    queryFn: () =>
      kelasService.getAll({
        semester: selectedSemester.semester,
        tahun_ajaran: selectedSemester.tahun_ajaran,
        status_kelas: 'buka',
      }),
    enabled: showAvailableClasses,
  });

  // Enroll mutation
  const enrollMutation = useMutation({
    mutationFn: (idKelas: number) =>
      enrollmentService.enroll({ nim, id_kelas: idKelas }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['krs'] });
      toast.success('Berhasil mendaftar mata kuliah');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal mendaftar mata kuliah');
    },
  });

  // Drop mutation
  const dropMutation = useMutation({
    mutationFn: (id: number) => enrollmentService.drop(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['krs'] });
      toast.success('Berhasil membatalkan mata kuliah');
      setIsDropDialogOpen(false);
      setSelectedEnrollment(null);
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal membatalkan mata kuliah');
    },
  });

  const handleEnroll = async (kelas: Kelas) => {
    // Check if already enrolled
    const isAlreadyEnrolled = krsData?.enrollments.some(
      (e) => e.id_kelas === kelas.id_kelas
    );

    if (isAlreadyEnrolled) {
      toast.warning('Anda sudah terdaftar di mata kuliah ini');
      return;
    }

    // Check capacity
    if (kelas.jumlah_mahasiswa && kelas.jumlah_mahasiswa >= kelas.kapasitas) {
      toast.error('Kelas sudah penuh');
      return;
    }

    enrollMutation.mutate(kelas.id_kelas);
  };

  const handleDrop = () => {
    if (selectedEnrollment) {
      dropMutation.mutate(selectedEnrollment.id_enrollment);
    }
  };

  const getStatusBadge = (status: string) => {
    const badges: Record<string, { color: string; label: string }> = {
      aktif: { color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', label: 'Aktif' },
      lulus: { color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', label: 'Lulus' },
      tidak_lulus: { color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', label: 'Tidak Lulus' },
      mengulang: { color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', label: 'Mengulang' },
      batal: { color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', label: 'Batal' },
    };
    const badge = badges[status] || badges.aktif;
    return (
      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${badge.color}`}>
        {badge.label}
      </span>
    );
  };

  if (!nim) {
    return (
      <div className="text-center py-12">
        <p className="text-red-600 dark:text-red-400">
          NIM tidak ditemukan. Silakan login kembali.
        </p>
      </div>
    );
  }

  if (krsError) {
    return (
      <div className="text-center py-12">
        <p className="text-red-600 dark:text-red-400">
          Error loading KRS: {(krsError as Error).message}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Kartu Rencana Studi (KRS)
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-2">
            Kelola mata kuliah yang akan diambil
          </p>
        </div>
      </div>

      {/* Semester Selection */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Semester
            </label>
            <select
              value={selectedSemester.semester}
              onChange={(e) =>
                setSelectedSemester({
                  ...selectedSemester,
                  semester: Number(e.target.value),
                })
              }
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
              Tahun Ajaran
            </label>
            <input
              type="text"
              value={selectedSemester.tahun_ajaran}
              onChange={(e) =>
                setSelectedSemester({
                  ...selectedSemester,
                  tahun_ajaran: e.target.value,
                })
              }
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
              placeholder="2024"
            />
          </div>
          <div className="flex items-end">
            <button
              onClick={() => setShowAvailableClasses(!showAvailableClasses)}
              className="w-full flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              <FiPlus />
              {showAvailableClasses ? 'Sembunyikan Kelas' : 'Tambah Mata Kuliah'}
            </button>
          </div>
        </div>
      </div>

      {/* KRS Summary */}
      {krsLoading ? (
        <SkeletonLoader className="h-24" />
      ) : krsData ? (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Total Mata Kuliah</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {krsData.enrollments?.length || 0}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Total SKS</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {krsData.total_sks || 0} SKS
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Status</p>
              <div className="flex items-center gap-2 mt-1">
                {krsData.total_sks && krsData.total_sks >= 12 && krsData.total_sks <= 24 ? (
                  <>
                    <FiCheckCircle className="text-green-600 dark:text-green-400" />
                    <span className="text-sm text-green-600 dark:text-green-400 font-medium">
                      SKS Normal
                    </span>
                  </>
                ) : (
                  <>
                    <FiAlertCircle className="text-yellow-600 dark:text-yellow-400" />
                    <span className="text-sm text-yellow-600 dark:text-yellow-400 font-medium">
                      {krsData.total_sks && krsData.total_sks < 12
                        ? 'SKS Kurang'
                        : 'SKS Melebihi Batas'}
                    </span>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {/* Available Classes */}
      {showAvailableClasses && (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Kelas Tersedia
          </h2>

          {classesLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {[1, 2, 3, 4].map((i) => (
                <SkeletonLoader key={i} className="h-32" />
              ))}
            </div>
          ) : availableClasses && availableClasses.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {availableClasses.map((kelas) => {
                const isEnrolled = krsData?.enrollments.some(
                  (e) => e.id_kelas === kelas.id_kelas
                );
                const isFull =
                  kelas.jumlah_mahasiswa && kelas.jumlah_mahasiswa >= kelas.kapasitas;

                return (
                  <div
                    key={kelas.id_kelas}
                    className="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                  >
                    <div className="flex justify-between items-start mb-3">
                      <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white">
                          {kelas.nama_mk}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          {kelas.nama_kelas}
                        </p>
                      </div>
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

                    <button
                      onClick={() => handleEnroll(kelas)}
                      disabled={isEnrolled || isFull || enrollMutation.isPending}
                      className={`w-full px-4 py-2 rounded-lg font-medium transition-colors ${
                        isEnrolled
                          ? 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                          : isFull
                          ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 cursor-not-allowed'
                          : 'bg-primary-600 hover:bg-primary-700 text-white'
                      }`}
                    >
                      {isEnrolled
                        ? 'Sudah Terdaftar'
                        : isFull
                        ? 'Kelas Penuh'
                        : 'Daftar'}
                    </button>
                  </div>
                );
              })}
            </div>
          ) : (
            <p className="text-center text-gray-500 dark:text-gray-400 py-8">
              Tidak ada kelas tersedia untuk semester ini
            </p>
          )}
        </div>
      )}

      {/* Current Enrollments */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Mata Kuliah Terdaftar
        </h2>

        {krsLoading ? (
          <SkeletonLoader className="h-64" />
        ) : krsData && krsData.enrollments && krsData.enrollments.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th className="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kode MK
                  </th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Mata Kuliah
                  </th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Kelas
                  </th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Status
                  </th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nilai
                  </th>
                  <th className="text-right py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody>
                {krsData.enrollments.map((enrollment) => (
                  <tr
                    key={enrollment.id_enrollment}
                    className="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                  >
                    <td className="py-3 px-4 text-sm text-gray-900 dark:text-white">
                      {enrollment.kode_mk}
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-900 dark:text-white">
                      {enrollment.nama_mk}
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-900 dark:text-white">
                      {enrollment.nama_kelas}
                    </td>
                    <td className="py-3 px-4">{getStatusBadge(enrollment.status_enrollment)}</td>
                    <td className="py-3 px-4 text-sm text-gray-900 dark:text-white">
                      {enrollment.nilai_huruf || '-'}
                      {enrollment.nilai_akhir ? ` (${enrollment.nilai_akhir.toFixed(2)})` : ''}
                    </td>
                    <td className="py-3 px-4 text-right">
                      {enrollment.status_enrollment === 'aktif' && (
                        <button
                          onClick={() => {
                            setSelectedEnrollment(enrollment);
                            setIsDropDialogOpen(true);
                          }}
                          className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                        >
                          <FiTrash2 size={16} />
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p className="text-center text-gray-500 dark:text-gray-400 py-8">
            Belum ada mata kuliah yang terdaftar
          </p>
        )}
      </div>

      {/* Drop Confirmation Dialog */}
      <ConfirmDialog
        isOpen={isDropDialogOpen}
        onCancel={() => {
          setIsDropDialogOpen(false);
          setSelectedEnrollment(null);
        }}
        onConfirm={handleDrop}
        title="Batalkan Mata Kuliah"
        message={`Apakah Anda yakin ingin membatalkan pendaftaran mata kuliah "${selectedEnrollment?.nama_mk}"?`}
        confirmText="Batalkan"
        confirmButtonClass="bg-red-600 hover:bg-red-700"
      />
    </div>
  );
};
