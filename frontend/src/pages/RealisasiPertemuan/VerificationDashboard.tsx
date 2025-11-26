import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import {
  FiCheckCircle,
  FiXCircle,
  FiEye,
  FiClock,
  FiAlertCircle,
  FiFileText,
} from 'react-icons/fi';
import { realisasiPertemuanService } from '../../services/realisasi-pertemuan.service';
import type { RealisasiPertemuan } from '../../types/api';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { VerificationModal } from '../../components/VerificationModal';

export const VerificationDashboard: React.FC = () => {
  const queryClient = useQueryClient();
  const [selectedRealisasi, setSelectedRealisasi] = useState<RealisasiPertemuan | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Fetch pending verifications
  const { data: pendingList, isLoading } = useQuery({
    queryKey: ['pending-verification'],
    queryFn: () => realisasiPertemuanService.getPendingVerification(),
    select: (response) => response.data?.data || [],
    refetchInterval: 30000, // Refetch every 30 seconds
  });

  // Verify mutation
  const verifyMutation = useMutation({
    mutationFn: ({ id, approved, komentar }: { id: number; approved: boolean; komentar?: string }) =>
      realisasiPertemuanService.verify(id, { approved, komentar }),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['pending-verification'] });
      toast.success(
        variables.approved
          ? 'Berita acara berhasil diverifikasi'
          : 'Berita acara ditolak'
      );
      setIsModalOpen(false);
      setSelectedRealisasi(null);
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal memverifikasi berita acara');
    },
  });

  const handleOpenModal = (realisasi: RealisasiPertemuan) => {
    setSelectedRealisasi(realisasi);
    setIsModalOpen(true);
  };

  const handleVerify = (approved: boolean, komentar?: string) => {
    if (selectedRealisasi?.id_realisasi) {
      verifyMutation.mutate({
        id: selectedRealisasi.id_realisasi,
        approved,
        komentar,
      });
    }
  };

  if (isLoading) {
    return <SkeletonLoader count={5} />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Verifikasi Berita Acara</h1>
        <p className="text-sm text-gray-600 mt-1">
          Review dan verifikasi berita acara perkuliahan dari dosen
        </p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-yellow-100 rounded-lg">
              <FiClock className="w-6 h-6 text-yellow-600" />
            </div>
            <div>
              <div className="text-sm text-gray-600">Menunggu Verifikasi</div>
              <div className="text-2xl font-bold text-gray-900">
                {pendingList?.length || 0}
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-100 rounded-lg">
              <FiFileText className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <div className="text-sm text-gray-600">Total Bulan Ini</div>
              <div className="text-2xl font-bold text-gray-900">-</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center gap-3">
            <div className="p-3 bg-green-100 rounded-lg">
              <FiCheckCircle className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <div className="text-sm text-gray-600">Terverifikasi Hari Ini</div>
              <div className="text-2xl font-bold text-gray-900">-</div>
            </div>
          </div>
        </div>
      </div>

      {/* List */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Daftar Pending Verifikasi</h2>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Tanggal Submit
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Tanggal Pelaksanaan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Dosen
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Mata Kuliah
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Kelas
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Pertemuan Ke
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {pendingList && pendingList.length > 0 ? (
                pendingList.map((item: RealisasiPertemuan) => (
                  <tr key={item.id_realisasi} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.created_at &&
                        new Date(item.created_at).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'short',
                          year: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {new Date(item.tanggal_pelaksanaan).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">{item.nama_dosen}</td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <div className="font-medium">{item.nama_mk}</div>
                      <div className="text-xs text-gray-500">{item.kode_mk}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.nama_kelas}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.minggu_ke || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        onClick={() => handleOpenModal(item)}
                        className="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                      >
                        <FiEye className="w-4 h-4" />
                        Review
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center text-gray-500">
                    <div className="flex flex-col items-center gap-2">
                      <FiCheckCircle className="w-12 h-12 text-gray-400" />
                      <p>Tidak ada berita acara yang menunggu verifikasi</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Verification Modal */}
      {selectedRealisasi && (
        <VerificationModal
          isOpen={isModalOpen}
          onClose={() => {
            setIsModalOpen(false);
            setSelectedRealisasi(null);
          }}
          realisasi={selectedRealisasi}
          onVerify={handleVerify}
          isSubmitting={verifyMutation.isPending}
        />
      )}
    </div>
  );
};
