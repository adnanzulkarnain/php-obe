import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import {
  FiPlus,
  FiEye,
  FiEdit2,
  FiSend,
  FiFilter,
  FiDownload,
  FiCheckCircle,
  FiXCircle,
  FiClock,
  FiFileText,
} from 'react-icons/fi';
import { realisasiPertemuanService } from '../../services/realisasi-pertemuan.service';
import type { RealisasiPertemuan } from '../../types/api';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { useAuth } from '../../hooks/useAuth';

export const RealisasiPertemuanList: React.FC = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [filters, setFilters] = useState<{
    id_kelas?: number;
    status?: string;
    tanggal_dari?: string;
    tanggal_sampai?: string;
  }>({});
  const [showFilters, setShowFilters] = useState(false);

  // Fetch realisasi list for current dosen
  const { data: realisasiList, isLoading } = useQuery({
    queryKey: ['realisasi-pertemuan', user?.ref_id, filters],
    queryFn: () =>
      realisasiPertemuanService.getAll({
        id_dosen: user?.ref_id,
        ...filters,
      }),
    select: (response) => response.data?.data || [],
  });

  // Fetch statistics
  const { data: statistics } = useQuery({
    queryKey: ['realisasi-statistics', user?.ref_id],
    queryFn: () => realisasiPertemuanService.getStatisticsByDosen(user?.ref_id || ''),
    select: (response) => response.data?.data,
    enabled: !!user?.ref_id,
  });

  const getStatusBadge = (status: string) => {
    const badges = {
      draft: {
        icon: FiFileText,
        class: 'bg-gray-100 text-gray-800',
        label: 'Draft',
      },
      submitted: {
        icon: FiClock,
        class: 'bg-yellow-100 text-yellow-800',
        label: 'Menunggu Verifikasi',
      },
      verified: {
        icon: FiCheckCircle,
        class: 'bg-green-100 text-green-800',
        label: 'Terverifikasi',
      },
      rejected: {
        icon: FiXCircle,
        class: 'bg-red-100 text-red-800',
        label: 'Ditolak',
      },
    };

    const badge = badges[status as keyof typeof badges] || badges.draft;
    const Icon = badge.icon;

    return (
      <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ${badge.class}`}>
        <Icon className="w-3.5 h-3.5" />
        {badge.label}
      </span>
    );
  };

  const handleExportPDF = async (id: number, namaMK: string, tanggal: string) => {
    try {
      const response = await realisasiPertemuanService.exportPDF(id);
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `Berita_Acara_${namaMK}_${tanggal}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Error exporting PDF:', error);
    }
  };

  if (isLoading) {
    return <SkeletonLoader count={5} />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Berita Acara Perkuliahan</h1>
          <p className="text-sm text-gray-600 mt-1">
            Kelola berita acara perkuliahan dan kehadiran mahasiswa
          </p>
        </div>
        <button
          onClick={() => navigate('/berita-acara/create')}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <FiPlus className="w-5 h-5" />
          Buat Berita Acara
        </button>
      </div>

      {/* Statistics Cards */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="bg-white rounded-lg shadow p-4">
            <div className="text-sm text-gray-600">Total Pertemuan</div>
            <div className="text-2xl font-bold text-gray-900 mt-1">
              {statistics.total_pertemuan || 0}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <div className="text-sm text-gray-600">Draft</div>
            <div className="text-2xl font-bold text-gray-600 mt-1">
              {statistics.draft_count || 0}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <div className="text-sm text-gray-600">Pending</div>
            <div className="text-2xl font-bold text-yellow-600 mt-1">
              {statistics.pending_count || 0}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <div className="text-sm text-gray-600">Terverifikasi</div>
            <div className="text-2xl font-bold text-green-600 mt-1">
              {statistics.verified_count || 0}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <div className="text-sm text-gray-600">Ditolak</div>
            <div className="text-2xl font-bold text-red-600 mt-1">
              {statistics.rejected_count || 0}
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white rounded-lg shadow">
        <div
          className="flex items-center justify-between p-4 cursor-pointer"
          onClick={() => setShowFilters(!showFilters)}
        >
          <div className="flex items-center gap-2">
            <FiFilter className="w-5 h-5 text-gray-600" />
            <span className="font-medium text-gray-900">Filter</span>
          </div>
          <span className="text-sm text-gray-500">
            {showFilters ? 'Sembunyikan' : 'Tampilkan'}
          </span>
        </div>

        {showFilters && (
          <div className="border-t p-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Status
                </label>
                <select
                  value={filters.status || ''}
                  onChange={(e) => setFilters({ ...filters, status: e.target.value || undefined })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Semua Status</option>
                  <option value="draft">Draft</option>
                  <option value="submitted">Menunggu Verifikasi</option>
                  <option value="verified">Terverifikasi</option>
                  <option value="rejected">Ditolak</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Tanggal Dari
                </label>
                <input
                  type="date"
                  value={filters.tanggal_dari || ''}
                  onChange={(e) =>
                    setFilters({ ...filters, tanggal_dari: e.target.value || undefined })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Tanggal Sampai
                </label>
                <input
                  type="date"
                  value={filters.tanggal_sampai || ''}
                  onChange={(e) =>
                    setFilters({ ...filters, tanggal_sampai: e.target.value || undefined })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>

            <div className="mt-4 flex gap-2">
              <button
                onClick={() => setFilters({})}
                className="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Reset Filter
              </button>
            </div>
          </div>
        )}
      </div>

      {/* List */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tanggal
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Mata Kuliah
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Kelas
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Pertemuan Ke
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {realisasiList && realisasiList.length > 0 ? (
                realisasiList.map((item: RealisasiPertemuan) => (
                  <tr key={item.id_realisasi} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {new Date(item.tanggal_pelaksanaan).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                    </td>
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
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(item.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => navigate(`/berita-acara/${item.id_realisasi}`)}
                          className="text-blue-600 hover:text-blue-900"
                          title="Lihat Detail"
                        >
                          <FiEye className="w-5 h-5" />
                        </button>

                        {(item.status === 'draft' || item.status === 'rejected') && (
                          <button
                            onClick={() =>
                              navigate(`/berita-acara/${item.id_realisasi}/edit`)
                            }
                            className="text-green-600 hover:text-green-900"
                            title="Edit"
                          >
                            <FiEdit2 className="w-5 h-5" />
                          </button>
                        )}

                        <button
                          onClick={() =>
                            handleExportPDF(
                              item.id_realisasi!,
                              item.nama_mk || '',
                              item.tanggal_pelaksanaan
                            )
                          }
                          className="text-purple-600 hover:text-purple-900"
                          title="Export PDF"
                        >
                          <FiDownload className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                    <div className="flex flex-col items-center gap-2">
                      <FiFileText className="w-12 h-12 text-gray-400" />
                      <p>Belum ada berita acara perkuliahan</p>
                      <button
                        onClick={() => navigate('/berita-acara/create')}
                        className="mt-2 text-blue-600 hover:text-blue-700"
                      >
                        Buat berita acara pertama
                      </button>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
