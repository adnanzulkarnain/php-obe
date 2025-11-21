import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import * as XLSX from 'xlsx';
import {
  FiTrendingUp,
  FiBarChart2,
  FiPieChart,
  FiFilter,
  FiDownload,
} from 'react-icons/fi';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import analyticsService, { type TrendData } from '../../services/analytics.service';
import { SkeletonLoader } from '../../components/SkeletonLoader';

const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

export const AnalyticsDashboard: React.FC = () => {
  const currentYear = new Date().getFullYear();
  const [filters, setFilters] = useState({
    id_prodi: '',
    start_year: (currentYear - 2).toString(),
    end_year: currentYear.toString(),
  });
  const [showFilters, setShowFilters] = useState(false);

  // Fetch trends data
  const {
    data: trendsData,
    isLoading: trendsLoading,
    error: trendsError,
  } = useQuery({
    queryKey: ['analytics-trends', filters],
    queryFn: () =>
      analyticsService.getTrends(
        filters.id_prodi || undefined,
        Number(filters.start_year),
        Number(filters.end_year)
      ),
  });

  if (trendsError) {
    toast.error('Gagal memuat data analytics');
  }

  // Transform data for charts
  const trendChartData = trendsData?.trends.map((trend: TrendData) => ({
    tahun: trend.tahun_ajaran,
    'Rata-rata Nilai': Number(trend.rata_nilai).toFixed(2),
    'Jumlah Mahasiswa': trend.jumlah_mahasiswa,
    'Lulus Baik (A-B)': trend.jumlah_lulus_baik,
  })) || [];

  // Grade distribution data (sample - replace with real API data)
  const gradeDistribution = [
    { grade: 'A', count: 45 },
    { grade: 'A-', count: 38 },
    { grade: 'B+', count: 52 },
    { grade: 'B', count: 41 },
    { grade: 'B-', count: 28 },
    { grade: 'C+', count: 15 },
    { grade: 'C', count: 8 },
    { grade: 'D', count: 3 },
  ];

  // Performance by semester (sample - replace with real API data)
  const performanceData = [
    { semester: 'Sem 1', avgGrade: 78 },
    { semester: 'Sem 2', avgGrade: 82 },
    { semester: 'Sem 3', avgGrade: 80 },
    { semester: 'Sem 4', avgGrade: 85 },
    { semester: 'Sem 5', avgGrade: 83 },
    { semester: 'Sem 6', avgGrade: 87 },
  ];

  const CustomTooltip = ({ active, payload, label }: any) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
          <p className="text-sm font-semibold text-gray-900 dark:text-white mb-2">{label}</p>
          {payload.map((entry: any, index: number) => (
            <p key={index} className="text-sm text-gray-600 dark:text-gray-400">
              <span style={{ color: entry.color }}>{entry.name}: </span>
              <span className="font-medium">{entry.value}</span>
            </p>
          ))}
        </div>
      );
    }
    return null;
  };

  const handleExportToExcel = () => {
    try {
      // Create a new workbook
      const wb = XLSX.utils.book_new();

      // Export Trend Data
      if (trendChartData.length > 0) {
        const trendWS = XLSX.utils.json_to_sheet(trendChartData);
        XLSX.utils.book_append_sheet(wb, trendWS, 'Trend Data');
      }

      // Export Grade Distribution
      const gradeWS = XLSX.utils.json_to_sheet(gradeDistribution);
      XLSX.utils.book_append_sheet(wb, gradeWS, 'Grade Distribution');

      // Export Performance Data
      const performanceWS = XLSX.utils.json_to_sheet(performanceData);
      XLSX.utils.book_append_sheet(wb, performanceWS, 'Performance by Semester');

      // Generate filename with current date
      const today = new Date().toISOString().split('T')[0];
      const filename = `Analytics_Report_${today}.xlsx`;

      // Write the workbook
      XLSX.writeFile(wb, filename);
      toast.success('Data berhasil diekspor ke Excel');
    } catch (error) {
      console.error('Export error:', error);
      toast.error('Gagal mengekspor data ke Excel');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Analytics Dashboard
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-2">
            Visualisasi dan analisis data pembelajaran
          </p>
        </div>
        <button
          onClick={handleExportToExcel}
          className="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
        >
          <FiDownload />
          Export to Excel
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
                ID Program Studi
              </label>
              <input
                type="text"
                value={filters.id_prodi}
                onChange={(e) => setFilters({ ...filters, id_prodi: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                placeholder="Semua Prodi"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Tahun Mulai
              </label>
              <input
                type="number"
                value={filters.start_year}
                onChange={(e) => setFilters({ ...filters, start_year: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Tahun Akhir
              </label>
              <input
                type="number"
                value={filters.end_year}
                onChange={(e) => setFilters({ ...filters, end_year: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        )}
      </div>

      {/* Charts Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Trend Chart */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div className="flex items-center gap-2 mb-4">
            <FiTrendingUp className="text-primary-600 dark:text-primary-400" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Trend Nilai & Mahasiswa
            </h2>
          </div>
          {trendsLoading ? (
            <SkeletonLoader className="h-80" />
          ) : trendChartData.length > 0 ? (
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={trendChartData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
                <XAxis
                  dataKey="tahun"
                  stroke="#6b7280"
                  style={{ fontSize: '12px' }}
                />
                <YAxis stroke="#6b7280" style={{ fontSize: '12px' }} />
                <Tooltip content={<CustomTooltip />} />
                <Legend wrapperStyle={{ fontSize: '12px' }} />
                <Line
                  type="monotone"
                  dataKey="Rata-rata Nilai"
                  stroke="#3b82f6"
                  strokeWidth={2}
                  dot={{ r: 4 }}
                  activeDot={{ r: 6 }}
                />
                <Line
                  type="monotone"
                  dataKey="Jumlah Mahasiswa"
                  stroke="#10b981"
                  strokeWidth={2}
                  dot={{ r: 4 }}
                  activeDot={{ r: 6 }}
                />
              </LineChart>
            </ResponsiveContainer>
          ) : (
            <p className="text-center text-gray-500 dark:text-gray-400 py-20">
              Tidak ada data trend
            </p>
          )}
        </div>

        {/* Grade Distribution */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div className="flex items-center gap-2 mb-4">
            <FiBarChart2 className="text-green-600 dark:text-green-400" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Distribusi Nilai
            </h2>
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={gradeDistribution}>
              <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
              <XAxis dataKey="grade" stroke="#6b7280" style={{ fontSize: '12px' }} />
              <YAxis stroke="#6b7280" style={{ fontSize: '12px' }} />
              <Tooltip content={<CustomTooltip />} />
              <Bar dataKey="count" fill="#10b981" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Performance by Semester */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div className="flex items-center gap-2 mb-4">
            <FiPieChart className="text-purple-600 dark:text-purple-400" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Performa Per Semester
            </h2>
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={performanceData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
              <XAxis dataKey="semester" stroke="#6b7280" style={{ fontSize: '12px' }} />
              <YAxis stroke="#6b7280" style={{ fontSize: '12px' }} domain={[0, 100]} />
              <Tooltip content={<CustomTooltip />} />
              <Bar dataKey="avgGrade" fill="#8b5cf6" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Success Rate Pie Chart */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div className="flex items-center gap-2 mb-4">
            <FiPieChart className="text-orange-600 dark:text-orange-400" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Tingkat Kelulusan
            </h2>
          </div>
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={[
                  { name: 'Lulus Baik (A-B)', value: trendsData?.trends.reduce((acc: number, t: TrendData) => acc + t.jumlah_lulus_baik, 0) || 176 },
                  { name: 'Lulus (C-D)', value: 54 },
                  { name: 'Tidak Lulus', value: 10 },
                ]}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name}: ${((percent || 0) * 100).toFixed(0)}%`}
                outerRadius={100}
                fill="#8884d8"
                dataKey="value"
              >
                {[0, 1, 2].map((_, index) => (
                  <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip content={<CustomTooltip />} />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
          <p className="text-sm opacity-90 mb-1">Total Data Points</p>
          <p className="text-3xl font-bold">{trendChartData.length}</p>
          <p className="text-xs opacity-75 mt-2">Tahun Ajaran</p>
        </div>

        <div className="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-sm p-6 text-white">
          <p className="text-sm opacity-90 mb-1">Total Mahasiswa</p>
          <p className="text-3xl font-bold">
            {trendsData?.trends.reduce((acc: number, t: TrendData) => acc + t.jumlah_mahasiswa, 0) || 0}
          </p>
          <p className="text-xs opacity-75 mt-2">Dalam periode</p>
        </div>

        <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
          <p className="text-sm opacity-90 mb-1">Avg Nilai</p>
          <p className="text-3xl font-bold">
            {trendsData?.trends.length
              ? (trendsData.trends.reduce((acc: number, t: TrendData) => acc + Number(t.rata_nilai), 0) / trendsData.trends.length).toFixed(2)
              : '0.00'}
          </p>
          <p className="text-xs opacity-75 mt-2">Overall average</p>
        </div>

        <div className="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-sm p-6 text-white">
          <p className="text-sm opacity-90 mb-1">Lulus Baik</p>
          <p className="text-3xl font-bold">
            {trendsData?.trends.reduce((acc: number, t: TrendData) => acc + t.jumlah_lulus_baik, 0) || 0}
          </p>
          <p className="text-xs opacity-75 mt-2">Grade A - B</p>
        </div>
      </div>
    </div>
  );
};
