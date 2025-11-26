import { useState } from 'react';
import { FiCheckCircle, FiXCircle, FiAlertCircle, FiSlash, FiSearch } from 'react-icons/fi';
import type { Kehadiran } from '../types/api';

interface KehadiranInputProps {
  kehadiran: Kehadiran[];
  onChange: (kehadiran: Kehadiran[]) => void;
  readonly?: boolean;
}

export const KehadiranInput: React.FC<KehadiranInputProps> = ({
  kehadiran,
  onChange,
  readonly = false,
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('');

  const updateKehadiran = (index: number, field: keyof Kehadiran, value: any) => {
    const updated = [...kehadiran];
    updated[index] = { ...updated[index], [field]: value };
    onChange(updated);
  };

  const setAllStatus = (status: 'hadir' | 'izin' | 'sakit' | 'alpha') => {
    const updated = kehadiran.map((k) => ({ ...k, status }));
    onChange(updated);
  };

  const filteredKehadiran = kehadiran.filter((k) => {
    const matchSearch =
      !searchTerm ||
      k.nama_mahasiswa?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      k.nim.toLowerCase().includes(searchTerm.toLowerCase());

    const matchStatus = !filterStatus || k.status === filterStatus;

    return matchSearch && matchStatus;
  });

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'hadir':
        return <FiCheckCircle className="w-5 h-5 text-green-600" />;
      case 'izin':
        return <FiAlertCircle className="w-5 h-5 text-blue-600" />;
      case 'sakit':
        return <FiAlertCircle className="w-5 h-5 text-yellow-600" />;
      case 'alpha':
        return <FiXCircle className="w-5 h-5 text-red-600" />;
      default:
        return <FiSlash className="w-5 h-5 text-gray-400" />;
    }
  };

  const getSummary = () => {
    const hadir = kehadiran.filter((k) => k.status === 'hadir').length;
    const izin = kehadiran.filter((k) => k.status === 'izin').length;
    const sakit = kehadiran.filter((k) => k.status === 'sakit').length;
    const alpha = kehadiran.filter((k) => k.status === 'alpha').length;
    const total = kehadiran.length;
    const persentase = total > 0 ? ((hadir / total) * 100).toFixed(1) : '0';

    return { hadir, izin, sakit, alpha, total, persentase };
  };

  const summary = getSummary();

  if (kehadiran.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        <p>Tidak ada data mahasiswa untuk kelas ini</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Summary */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div className="bg-gray-50 rounded-lg p-3">
          <div className="text-xs text-gray-600">Total</div>
          <div className="text-xl font-bold text-gray-900">{summary.total}</div>
        </div>
        <div className="bg-green-50 rounded-lg p-3">
          <div className="text-xs text-green-700">Hadir</div>
          <div className="text-xl font-bold text-green-700">{summary.hadir}</div>
        </div>
        <div className="bg-blue-50 rounded-lg p-3">
          <div className="text-xs text-blue-700">Izin</div>
          <div className="text-xl font-bold text-blue-700">{summary.izin}</div>
        </div>
        <div className="bg-yellow-50 rounded-lg p-3">
          <div className="text-xs text-yellow-700">Sakit</div>
          <div className="text-xl font-bold text-yellow-700">{summary.sakit}</div>
        </div>
        <div className="bg-red-50 rounded-lg p-3">
          <div className="text-xs text-red-700">Alpha</div>
          <div className="text-xl font-bold text-red-700">{summary.alpha}</div>
        </div>
      </div>

      <div className="flex items-center justify-between text-sm">
        <span className="text-gray-600">
          Persentase Kehadiran: <strong>{summary.persentase}%</strong>
        </span>
      </div>

      {/* Filters and Quick Actions */}
      {!readonly && (
        <div className="flex flex-col md:flex-row gap-3">
          <div className="flex-1 relative">
            <FiSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Cari mahasiswa (nama/NIM)..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="hadir">Hadir</option>
            <option value="izin">Izin</option>
            <option value="sakit">Sakit</option>
            <option value="alpha">Alpha</option>
          </select>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => setAllStatus('hadir')}
              className="px-3 py-2 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors"
              title="Tandai semua hadir"
            >
              Semua Hadir
            </button>
            <button
              type="button"
              onClick={() => setAllStatus('alpha')}
              className="px-3 py-2 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
              title="Tandai semua alpha"
            >
              Semua Alpha
            </button>
          </div>
        </div>
      )}

      {/* Table */}
      <div className="border border-gray-200 rounded-lg overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  No
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  NIM
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Nama
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Status
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Keterangan
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredKehadiran.map((item, index) => {
                const originalIndex = kehadiran.findIndex((k) => k.nim === item.nim);
                return (
                  <tr key={item.nim} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-sm text-gray-900">{index + 1}</td>
                    <td className="px-4 py-3 text-sm text-gray-900 font-mono">
                      {item.nim}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-900">
                      {item.nama_mahasiswa}
                    </td>
                    <td className="px-4 py-3">
                      {readonly ? (
                        <div className="flex items-center gap-2">
                          {getStatusIcon(item.status)}
                          <span className="text-sm capitalize">{item.status}</span>
                        </div>
                      ) : (
                        <div className="flex items-center gap-2">
                          <button
                            type="button"
                            onClick={() => updateKehadiran(originalIndex, 'status', 'hadir')}
                            className={`p-1.5 rounded ${
                              item.status === 'hadir'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-gray-100 text-gray-400 hover:bg-green-50'
                            }`}
                            title="Hadir"
                          >
                            <FiCheckCircle className="w-4 h-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => updateKehadiran(originalIndex, 'status', 'izin')}
                            className={`p-1.5 rounded ${
                              item.status === 'izin'
                                ? 'bg-blue-100 text-blue-700'
                                : 'bg-gray-100 text-gray-400 hover:bg-blue-50'
                            }`}
                            title="Izin"
                          >
                            <FiAlertCircle className="w-4 h-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => updateKehadiran(originalIndex, 'status', 'sakit')}
                            className={`p-1.5 rounded ${
                              item.status === 'sakit'
                                ? 'bg-yellow-100 text-yellow-700'
                                : 'bg-gray-100 text-gray-400 hover:bg-yellow-50'
                            }`}
                            title="Sakit"
                          >
                            <FiAlertCircle className="w-4 h-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => updateKehadiran(originalIndex, 'status', 'alpha')}
                            className={`p-1.5 rounded ${
                              item.status === 'alpha'
                                ? 'bg-red-100 text-red-700'
                                : 'bg-gray-100 text-gray-400 hover:bg-red-50'
                            }`}
                            title="Alpha"
                          >
                            <FiXCircle className="w-4 h-4" />
                          </button>
                        </div>
                      )}
                    </td>
                    <td className="px-4 py-3">
                      {readonly ? (
                        <span className="text-sm text-gray-600">{item.keterangan || '-'}</span>
                      ) : (
                        <input
                          type="text"
                          value={item.keterangan || ''}
                          onChange={(e) =>
                            updateKehadiran(originalIndex, 'keterangan', e.target.value)
                          }
                          placeholder="Keterangan (opsional)"
                          className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        />
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {filteredKehadiran.length === 0 && (
          <div className="py-8 text-center text-gray-500">
            Tidak ada mahasiswa yang sesuai dengan filter
          </div>
        )}
      </div>
    </div>
  );
};
