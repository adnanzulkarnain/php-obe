import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  FiX,
  FiCheckCircle,
  FiXCircle,
  FiAlertCircle,
  FiBook,
  FiUsers,
} from 'react-icons/fi';
import { realisasiPertemuanService } from '../services/realisasi-pertemuan.service';
import type { RealisasiPertemuan, RPSComparison } from '../types/api';
import { KehadiranInput } from './KehadiranInput';

interface VerificationModalProps {
  isOpen: boolean;
  onClose: () => void;
  realisasi: RealisasiPertemuan;
  onVerify: (approved: boolean, komentar?: string) => void;
  isSubmitting: boolean;
}

export const VerificationModal: React.FC<VerificationModalProps> = ({
  isOpen,
  onClose,
  realisasi,
  onVerify,
  isSubmitting,
}) => {
  const [activeTab, setActiveTab] = useState<'detail' | 'kehadiran' | 'comparison'>('detail');
  const [decision, setDecision] = useState<'approve' | 'reject' | null>(null);
  const [komentar, setKomentar] = useState('');

  // Fetch full details
  const { data: fullData } = useQuery({
    queryKey: ['realisasi-detail', realisasi.id_realisasi],
    queryFn: () => realisasiPertemuanService.getById(realisasi.id_realisasi!),
    select: (response) => response.data?.data,
    enabled: isOpen,
  });

  // Fetch RPS comparison
  const { data: comparison } = useQuery<RPSComparison>({
    queryKey: ['rps-comparison', realisasi.id_realisasi],
    queryFn: () => realisasiPertemuanService.compareWithRPS(realisasi.id_realisasi!),
    select: (response) => response.data?.data,
    enabled: isOpen && !!realisasi.id_minggu,
  });

  useEffect(() => {
    if (!isOpen) {
      setDecision(null);
      setKomentar('');
      setActiveTab('detail');
    }
  }, [isOpen]);

  if (!isOpen) return null;

  const handleSubmit = () => {
    if (decision) {
      onVerify(decision === 'approve', komentar || undefined);
    }
  };

  const data = fullData || realisasi;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {/* Overlay */}
        <div
          className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
          onClick={onClose}
        />

        {/* Modal */}
        <div className="inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
          {/* Header */}
          <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">
                Review Berita Acara Perkuliahan
              </h3>
              <p className="text-sm text-gray-600 mt-1">
                {data.nama_mk} - {data.nama_kelas} | {data.nama_dosen}
              </p>
            </div>
            <button
              onClick={onClose}
              className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <FiX className="w-5 h-5" />
            </button>
          </div>

          {/* Tabs */}
          <div className="border-b border-gray-200">
            <div className="flex">
              <button
                onClick={() => setActiveTab('detail')}
                className={`px-6 py-3 text-sm font-medium border-b-2 transition-colors ${
                  activeTab === 'detail'
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-600 hover:text-gray-900'
                }`}
              >
                <div className="flex items-center gap-2">
                  <FiBook className="w-4 h-4" />
                  Detail Perkuliahan
                </div>
              </button>
              <button
                onClick={() => setActiveTab('kehadiran')}
                className={`px-6 py-3 text-sm font-medium border-b-2 transition-colors ${
                  activeTab === 'kehadiran'
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-600 hover:text-gray-900'
                }`}
              >
                <div className="flex items-center gap-2">
                  <FiUsers className="w-4 h-4" />
                  Kehadiran ({data.kehadiran?.length || 0})
                </div>
              </button>
              {comparison && (
                <button
                  onClick={() => setActiveTab('comparison')}
                  className={`px-6 py-3 text-sm font-medium border-b-2 transition-colors ${
                    activeTab === 'comparison'
                      ? 'border-blue-600 text-blue-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <FiAlertCircle className="w-4 h-4" />
                    Perbandingan RPS
                  </div>
                </button>
              )}
            </div>
          </div>

          {/* Content */}
          <div className="px-6 py-4 max-h-[60vh] overflow-y-auto">
            {/* Detail Tab */}
            {activeTab === 'detail' && (
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Tanggal Pelaksanaan
                    </label>
                    <p className="mt-1 text-sm text-gray-900">
                      {new Date(data.tanggal_pelaksanaan).toLocaleDateString('id-ID', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </p>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Pertemuan Ke
                    </label>
                    <p className="mt-1 text-sm text-gray-900">{data.minggu_ke || '-'}</p>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    Materi yang Disampaikan
                  </label>
                  <div className="mt-1 p-3 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-900 whitespace-pre-wrap">
                      {data.materi_disampaikan || '-'}
                    </p>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">
                    Metode Pembelajaran
                  </label>
                  <div className="mt-1 p-3 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-900 whitespace-pre-wrap">
                      {data.metode_digunakan || '-'}
                    </p>
                  </div>
                </div>

                {data.kendala && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Kendala</label>
                    <div className="mt-1 p-3 bg-yellow-50 rounded-lg">
                      <p className="text-sm text-gray-900 whitespace-pre-wrap">
                        {data.kendala}
                      </p>
                    </div>
                  </div>
                )}

                {data.catatan_dosen && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Catatan Dosen
                    </label>
                    <div className="mt-1 p-3 bg-blue-50 rounded-lg">
                      <p className="text-sm text-gray-900 whitespace-pre-wrap">
                        {data.catatan_dosen}
                      </p>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Kehadiran Tab */}
            {activeTab === 'kehadiran' && (
              <div>
                {data.kehadiran && data.kehadiran.length > 0 ? (
                  <KehadiranInput
                    kehadiran={data.kehadiran}
                    onChange={() => {}}
                    readonly={true}
                  />
                ) : (
                  <div className="text-center py-8 text-gray-500">
                    Tidak ada data kehadiran
                  </div>
                )}
              </div>
            )}

            {/* Comparison Tab */}
            {activeTab === 'comparison' && comparison && (
              <div className="space-y-4">
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <h4 className="font-medium text-blue-900 mb-2">
                    Rencana dari RPS (Pertemuan ke-{comparison.minggu_ke})
                  </h4>
                  {comparison.rencana_materi && (
                    <div className="mt-2">
                      <div className="text-sm font-medium text-blue-900">Materi:</div>
                      <div className="text-sm text-blue-800">
                        {JSON.stringify(comparison.rencana_materi)}
                      </div>
                    </div>
                  )}
                  {comparison.rencana_metode && (
                    <div className="mt-2">
                      <div className="text-sm font-medium text-blue-900">Metode:</div>
                      <div className="text-sm text-blue-800">
                        {JSON.stringify(comparison.rencana_metode)}
                      </div>
                    </div>
                  )}
                  {comparison.deskripsi_subcpmk && (
                    <div className="mt-2">
                      <div className="text-sm font-medium text-blue-900">SubCPMK:</div>
                      <div className="text-sm text-blue-800">
                        {comparison.kode_subcpmk} - {comparison.deskripsi_subcpmk}
                      </div>
                    </div>
                  )}
                </div>

                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                  <h4 className="font-medium text-green-900 mb-2">Realisasi</h4>
                  <div className="mt-2">
                    <div className="text-sm font-medium text-green-900">Materi:</div>
                    <div className="text-sm text-green-800">
                      {comparison.materi_disampaikan}
                    </div>
                  </div>
                  {comparison.metode_digunakan && (
                    <div className="mt-2">
                      <div className="text-sm font-medium text-green-900">Metode:</div>
                      <div className="text-sm text-green-800">
                        {comparison.metode_digunakan}
                      </div>
                    </div>
                  )}
                </div>

                {comparison.analysis && (
                  <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 className="font-medium text-gray-900 mb-2">Analisis</h4>
                    <div className="text-sm text-gray-700">
                      {comparison.analysis.has_plan ? (
                        <div className="flex items-start gap-2">
                          <FiCheckCircle className="w-5 h-5 text-green-600 mt-0.5" />
                          <div>
                            <p>Materi sesuai dengan rencana RPS</p>
                            {comparison.analysis.material_match !== undefined && (
                              <p className="text-xs text-gray-600 mt-1">
                                Kesesuaian: {comparison.analysis.material_match}%
                              </p>
                            )}
                          </div>
                        </div>
                      ) : (
                        <div className="flex items-start gap-2">
                          <FiAlertCircle className="w-5 h-5 text-yellow-600 mt-0.5" />
                          <p>Tidak ada rencana mingguan terkait di RPS</p>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Verification Section */}
          <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Keputusan Verifikasi
                </label>
                <div className="flex gap-3">
                  <button
                    type="button"
                    onClick={() => setDecision('approve')}
                    className={`flex-1 px-4 py-3 rounded-lg border-2 transition-all ${
                      decision === 'approve'
                        ? 'border-green-600 bg-green-50 text-green-700'
                        : 'border-gray-300 bg-white text-gray-700 hover:border-green-400'
                    }`}
                  >
                    <div className="flex items-center justify-center gap-2">
                      <FiCheckCircle className="w-5 h-5" />
                      <span className="font-medium">Terverifikasi</span>
                    </div>
                    <p className="text-xs mt-1">Materi sesuai dengan RPS</p>
                  </button>

                  <button
                    type="button"
                    onClick={() => setDecision('reject')}
                    className={`flex-1 px-4 py-3 rounded-lg border-2 transition-all ${
                      decision === 'reject'
                        ? 'border-red-600 bg-red-50 text-red-700'
                        : 'border-gray-300 bg-white text-gray-700 hover:border-red-400'
                    }`}
                  >
                    <div className="flex items-center justify-center gap-2">
                      <FiXCircle className="w-5 h-5" />
                      <span className="font-medium">Ditolak</span>
                    </div>
                    <p className="text-xs mt-1">Perlu perbaikan</p>
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Komentar {decision === 'reject' && <span className="text-red-500">*</span>}
                </label>
                <textarea
                  value={komentar}
                  onChange={(e) => setKomentar(e.target.value)}
                  rows={3}
                  placeholder={
                    decision === 'reject'
                      ? 'Jelaskan alasan penolakan dan perbaikan yang perlu dilakukan...'
                      : 'Komentar atau feedback (opsional)...'
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              disabled={isSubmitting}
            >
              Batal
            </button>
            <button
              type="button"
              onClick={handleSubmit}
              disabled={!decision || (decision === 'reject' && !komentar.trim()) || isSubmitting}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSubmitting ? 'Memproses...' : 'Submit Verifikasi'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
