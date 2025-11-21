import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import {
  FiCheckCircle,
  FiXCircle,
  FiClock,
  FiFileText,
  FiUser,
  FiCalendar,
} from 'react-icons/fi';
import { rpsService, type RPS } from '../../services/rps.service';
import { SkeletonLoader } from '../../components/SkeletonLoader';
import { ConfirmDialog } from '../../components/ConfirmDialog';

interface ApprovalRequest extends RPS {
  id_approval?: number;
  nama_dosen?: string;
  nama_mk?: string;
  submitted_at?: string;
}

export const RPSApprovalPage: React.FC = () => {
  const queryClient = useQueryClient();
  const [selectedRPS, setSelectedRPS] = useState<ApprovalRequest | null>(null);
  const [isApproveDialogOpen, setIsApproveDialogOpen] = useState(false);
  const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);
  const [catatan, setCatatan] = useState('');

  // Fetch pending approvals
  const {
    data: pendingApprovals,
    isLoading,
    error,
  } = useQuery<ApprovalRequest[]>({
    queryKey: ['rps-pending-approvals'],
    queryFn: async () => {
      const response = await rpsService.getPendingApprovals();
      return response.data.data || [];
    },
  });

  // Approval mutation
  const approvalMutation = useMutation({
    mutationFn: ({
      idApproval,
      action,
      catatan,
    }: {
      idApproval: number;
      action: 'approve' | 'reject';
      catatan?: string;
    }) => rpsService.processApproval(idApproval, action, catatan),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['rps-pending-approvals'] });
      toast.success(
        variables.action === 'approve'
          ? 'RPS berhasil disetujui'
          : 'RPS berhasil ditolak'
      );
      setIsApproveDialogOpen(false);
      setIsRejectDialogOpen(false);
      setSelectedRPS(null);
      setCatatan('');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Gagal memproses approval');
    },
  });

  const handleApprove = () => {
    if (selectedRPS && selectedRPS.id_approval) {
      approvalMutation.mutate({
        idApproval: selectedRPS.id_approval,
        action: 'approve',
        catatan,
      });
    }
  };

  const handleReject = () => {
    if (selectedRPS && selectedRPS.id_approval) {
      if (!catatan.trim()) {
        toast.warning('Mohon berikan catatan untuk penolakan');
        return;
      }
      approvalMutation.mutate({
        idApproval: selectedRPS.id_approval,
        action: 'reject',
        catatan,
      });
    }
  };

  const getStatusBadge = (status: string) => {
    const badges: Record<string, { color: string; label: string; icon: any }> = {
      draft: {
        color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        label: 'Draft',
        icon: FiFileText,
      },
      submitted: {
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        label: 'Pending Approval',
        icon: FiClock,
      },
      approved: {
        color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        label: 'Approved',
        icon: FiCheckCircle,
      },
      active: {
        color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        label: 'Active',
        icon: FiCheckCircle,
      },
      archived: {
        color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        label: 'Archived',
        icon: FiFileText,
      },
    };
    const badge = badges[status] || badges.draft;
    const Icon = badge.icon;
    return (
      <span className={`inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full ${badge.color}`}>
        <Icon size={12} />
        {badge.label}
      </span>
    );
  };

  if (error) {
    return (
      <div className="text-center py-12">
        <p className="text-red-600 dark:text-red-400">
          Error loading pending approvals: {(error as Error).message}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          RPS Approval Workflow
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Review dan setujui Rencana Pembelajaran Semester yang diajukan
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-sm p-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm opacity-90 mb-1">Pending Approval</p>
              <p className="text-3xl font-bold">{pendingApprovals?.length || 0}</p>
            </div>
            <FiClock className="text-4xl opacity-80" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-sm p-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm opacity-90 mb-1">Approved Today</p>
              <p className="text-3xl font-bold">0</p>
            </div>
            <FiCheckCircle className="text-4xl opacity-80" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-sm p-6 text-white">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm opacity-90 mb-1">Rejected Today</p>
              <p className="text-3xl font-bold">0</p>
            </div>
            <FiXCircle className="text-4xl opacity-80" />
          </div>
        </div>
      </div>

      {/* Pending Approvals List */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
            Pending Approvals
          </h2>
        </div>

        <div className="p-6">
          {isLoading ? (
            <div className="space-y-4">
              {[1, 2, 3].map((i) => (
                <SkeletonLoader key={i} className="h-32" />
              ))}
            </div>
          ) : pendingApprovals && pendingApprovals.length > 0 ? (
            <div className="space-y-4">
              {pendingApprovals.map((rps) => (
                <div
                  key={rps.id_rps}
                  className="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-shadow"
                >
                  <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    {/* RPS Info */}
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-3">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                          {rps.nama_mk || rps.kode_mk}
                        </h3>
                        {getStatusBadge(rps.status)}
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <div className="flex items-center gap-2">
                          <FiUser className="flex-shrink-0" />
                          <span>Dosen: {rps.nama_dosen || 'N/A'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <FiFileText className="flex-shrink-0" />
                          <span>Kode MK: {rps.kode_mk}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <FiCalendar className="flex-shrink-0" />
                          <span>
                            Tahun Akademik: {rps.tahun_akademik} - Semester {rps.semester}
                          </span>
                        </div>
                        <div className="flex items-center gap-2">
                          <FiClock className="flex-shrink-0" />
                          <span>
                            Versi: {rps.versi}
                            {rps.submitted_at &&
                              ` - Submitted: ${new Date(rps.submitted_at).toLocaleDateString('id-ID')}`}
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex flex-col sm:flex-row gap-2 lg:flex-col">
                      <button
                        onClick={() => {
                          setSelectedRPS(rps);
                          setIsApproveDialogOpen(true);
                        }}
                        className="flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                      >
                        <FiCheckCircle />
                        Approve
                      </button>
                      <button
                        onClick={() => {
                          setSelectedRPS(rps);
                          setIsRejectDialogOpen(true);
                        }}
                        className="flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                      >
                        <FiXCircle />
                        Reject
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <FiCheckCircle className="mx-auto text-6xl text-gray-300 dark:text-gray-600 mb-4" />
              <p className="text-gray-500 dark:text-gray-400">
                Tidak ada RPS yang menunggu approval
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Approve Dialog */}
      <ConfirmDialog
        isOpen={isApproveDialogOpen}
        onCancel={() => {
          setIsApproveDialogOpen(false);
          setSelectedRPS(null);
          setCatatan('');
        }}
        onConfirm={handleApprove}
        title="Approve RPS"
        message={`Apakah Anda yakin ingin menyetujui RPS untuk mata kuliah "${selectedRPS?.nama_mk || selectedRPS?.kode_mk}"?`}
        confirmText="Approve"
        type="info"
      />

      {/* Reject Dialog */}
      {isRejectDialogOpen && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
              Reject RPS
            </h2>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              Berikan catatan mengapa RPS ditolak:
            </p>
            <textarea
              value={catatan}
              onChange={(e) => setCatatan(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white mb-4"
              rows={4}
              placeholder="Masukkan catatan penolakan..."
            />
            <div className="flex gap-3">
              <button
                onClick={() => {
                  setIsRejectDialogOpen(false);
                  setSelectedRPS(null);
                  setCatatan('');
                }}
                className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button
                onClick={handleReject}
                disabled={approvalMutation.isPending}
                className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
              >
                {approvalMutation.isPending ? 'Processing...' : 'Reject'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
