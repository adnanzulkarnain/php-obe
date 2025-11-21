import React, { useState } from 'react';
import { Wizard } from '../../components/Wizard';
import type { WizardStep } from '../../components/Wizard';
import { rpsService } from '../../services/rps.service';
import type { CreateRPSData } from '../../services/rps.service';
import { cplService } from '../../services/cpl.service';
import type { Kurikulum, MataKuliah, CPL } from '../../types/api';
import type { Dosen } from '../../services/dosen.service';
import { toast } from 'react-toastify';
import { FiInfo, FiFileText, FiTarget, FiCheckCircle } from 'react-icons/fi';

interface RPSWizardProps {
  kurikulumId: number;
  kurikulums: Kurikulum[];
  mataKuliahs: MataKuliah[];
  dosens: Dosen[];
  onClose: () => void;
  onSuccess: () => void;
}

export const RPSWizard: React.FC<RPSWizardProps> = ({
  kurikulumId,
  kurikulums,
  mataKuliahs,
  dosens,
  onClose,
  onSuccess,
}) => {
  const currentYear = new Date().getFullYear();
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Form state
  const [formData, setFormData] = useState<CreateRPSData>({
    kode_mk: '',
    id_kurikulum: kurikulumId,
    semester_berlaku: 'Ganjil',
    tahun_ajaran: `${currentYear}/${currentYear + 1}`,
    ketua_pengembang: '',
    tanggal_disusun: new Date().toISOString().split('T')[0],
    deskripsi_mk: '',
    deskripsi_singkat: '',
  });

  // CPMK state (for Step 3)
  const [cpmkList, setCpmkList] = useState<Array<{ kode: string; deskripsi: string }>>([]);

  const handleComplete = async () => {
    setIsSubmitting(true);
    try {
      // Create RPS
      await rpsService.create(formData);

      toast.success('RPS created successfully!');
      onSuccess();
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to create RPS');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Step validation functions
  const validateStep1 = () => {
    if (!formData.kode_mk) {
      toast.error('Please select Mata Kuliah');
      return false;
    }
    if (!formData.ketua_pengembang) {
      toast.error('Please select Ketua Pengembang');
      return false;
    }
    if (!formData.tahun_ajaran || !formData.tahun_ajaran.match(/^\d{4}\/\d{4}$/)) {
      toast.error('Please enter valid Tahun Ajaran (e.g., 2024/2025)');
      return false;
    }
    return true;
  };

  const validateStep2 = () => {
    if (!formData.deskripsi_mk || formData.deskripsi_mk.trim().length < 20) {
      toast.error('Deskripsi Mata Kuliah must be at least 20 characters');
      return false;
    }
    if (!formData.deskripsi_singkat || formData.deskripsi_singkat.trim().length < 10) {
      toast.error('Deskripsi Singkat must be at least 10 characters');
      return false;
    }
    return true;
  };

  const steps: WizardStep[] = [
    {
      id: 'basic-info',
      title: 'Basic Info',
      description: 'RPS Information',
      component: (
        <Step1BasicInfo
          formData={formData}
          setFormData={setFormData}
          kurikulums={kurikulums}
          mataKuliahs={mataKuliahs}
          dosens={dosens}
        />
      ),
      validate: validateStep1,
    },
    {
      id: 'course-description',
      title: 'Description',
      description: 'Course Details',
      component: (
        <Step2CourseDescription
          formData={formData}
          setFormData={setFormData}
        />
      ),
      validate: validateStep2,
    },
    {
      id: 'learning-outcomes',
      title: 'CPMK',
      description: 'Learning Outcomes',
      component: (
        <Step3LearningOutcomes
          cpmkList={cpmkList}
          setCpmkList={setCpmkList}
          kurikulumId={formData.id_kurikulum}
        />
      ),
    },
    {
      id: 'review',
      title: 'Review',
      description: 'Review & Submit',
      component: (
        <Step4Review
          formData={formData}
          cpmkList={cpmkList}
          mataKuliahs={mataKuliahs}
          dosens={dosens}
        />
      ),
    },
  ];

  return (
    <Wizard
      steps={steps}
      onComplete={handleComplete}
      onCancel={onClose}
      title="Create New RPS"
      isSubmitting={isSubmitting}
    />
  );
};

// ============= STEP 1: Basic Information =============
interface Step1Props {
  formData: CreateRPSData;
  setFormData: React.Dispatch<React.SetStateAction<CreateRPSData>>;
  kurikulums: Kurikulum[];
  mataKuliahs: MataKuliah[];
  dosens: Dosen[];
}

const Step1BasicInfo: React.FC<Step1Props> = ({
  formData,
  setFormData,
  kurikulums,
  mataKuliahs,
  dosens,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-start space-x-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <FiInfo className="text-blue-500 text-xl mt-0.5" />
        <div>
          <h3 className="font-medium text-blue-900 dark:text-blue-100">Basic RPS Information</h3>
          <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
            Enter the basic information for this Rencana Pembelajaran Semester.
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Kurikulum <span className="text-red-500">*</span>
          </label>
          <select
            value={formData.id_kurikulum}
            onChange={(e) => setFormData({ ...formData, id_kurikulum: Number(e.target.value) })}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            disabled
          >
            {kurikulums.map((k) => (
              <option key={k.id_kurikulum} value={k.id_kurikulum}>
                {k.kode_kurikulum} - {k.nama_kurikulum} ({k.tahun_berlaku})
              </option>
            ))}
          </select>
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Selected based on current filter
          </p>
        </div>

        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Mata Kuliah <span className="text-red-500">*</span>
          </label>
          <select
            value={formData.kode_mk}
            onChange={(e) => setFormData({ ...formData, kode_mk: e.target.value })}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          >
            <option value="">Select Mata Kuliah</option>
            {mataKuliahs.map((mk) => (
              <option key={mk.kode_mk} value={mk.kode_mk}>
                {mk.kode_mk} - {mk.nama_mk} ({mk.sks} SKS)
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Semester Berlaku <span className="text-red-500">*</span>
          </label>
          <select
            value={formData.semester_berlaku}
            onChange={(e) => setFormData({ ...formData, semester_berlaku: e.target.value })}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          >
            <option value="Ganjil">Ganjil</option>
            <option value="Genap">Genap</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Tahun Ajaran <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            value={formData.tahun_ajaran}
            onChange={(e) => setFormData({ ...formData, tahun_ajaran: e.target.value })}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            placeholder="2024/2025"
            required
          />
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Format: YYYY/YYYY</p>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Ketua Pengembang <span className="text-red-500">*</span>
          </label>
          <select
            value={formData.ketua_pengembang}
            onChange={(e) => setFormData({ ...formData, ketua_pengembang: e.target.value })}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          >
            <option value="">Select Dosen</option>
            {dosens.map((d) => (
              <option key={d.id_dosen} value={d.id_dosen}>
                {d.nama} - {d.nidn}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Tanggal Disusun
          </label>
          <input
            type="date"
            value={formData.tanggal_disusun}
            onChange={(e) => setFormData({ ...formData, tanggal_disusun: e.target.value })}
            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
        </div>
      </div>
    </div>
  );
};

// ============= STEP 2: Course Description =============
interface Step2Props {
  formData: CreateRPSData;
  setFormData: React.Dispatch<React.SetStateAction<CreateRPSData>>;
}

const Step2CourseDescription: React.FC<Step2Props> = ({ formData, setFormData }) => {
  return (
    <div className="space-y-6">
      <div className="flex items-start space-x-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <FiFileText className="text-blue-500 text-xl mt-0.5" />
        <div>
          <h3 className="font-medium text-blue-900 dark:text-blue-100">Course Description</h3>
          <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
            Provide detailed and brief descriptions of the course.
          </p>
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Deskripsi Mata Kuliah (Full Description) <span className="text-red-500">*</span>
        </label>
        <textarea
          value={formData.deskripsi_mk}
          onChange={(e) => setFormData({ ...formData, deskripsi_mk: e.target.value })}
          className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          rows={8}
          placeholder="Enter comprehensive course description including objectives, scope, and main topics..."
          required
        />
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Minimum 20 characters. Current: {formData.deskripsi_mk?.length || 0}
        </p>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Deskripsi Singkat (Brief Description) <span className="text-red-500">*</span>
        </label>
        <textarea
          value={formData.deskripsi_singkat}
          onChange={(e) => setFormData({ ...formData, deskripsi_singkat: e.target.value })}
          className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          rows={4}
          placeholder="Enter brief course summary (2-3 sentences)..."
          required
        />
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Minimum 10 characters. Current: {formData.deskripsi_singkat?.length || 0}
        </p>
      </div>
    </div>
  );
};

// ============= STEP 3: Learning Outcomes (CPMK) =============
interface Step3Props {
  cpmkList: Array<{ kode: string; deskripsi: string }>;
  setCpmkList: React.Dispatch<React.SetStateAction<Array<{ kode: string; deskripsi: string }>>>;
  kurikulumId: number;
}

const Step3LearningOutcomes: React.FC<Step3Props> = ({ cpmkList, setCpmkList, kurikulumId }) => {
  const [newCpmk, setNewCpmk] = useState({ kode: '', deskripsi: '' });
  const [availableCPL, setAvailableCPL] = useState<CPL[]>([]);

  React.useEffect(() => {
    loadCPL();
  }, [kurikulumId]);

  const loadCPL = async () => {
    try {
      const response = await cplService.getAll({ id_kurikulum: kurikulumId });
      if (response.data && Array.isArray(response.data)) {
        setAvailableCPL(response.data.filter(cpl => cpl.is_active));
      }
    } catch (error) {
      console.error('Failed to load CPL:', error);
    }
  };

  const handleAddCpmk = () => {
    if (!newCpmk.kode || !newCpmk.deskripsi) {
      toast.error('Please fill in both CPMK code and description');
      return;
    }

    if (cpmkList.some(c => c.kode === newCpmk.kode)) {
      toast.error('CPMK code already exists');
      return;
    }

    setCpmkList([...cpmkList, { ...newCpmk }]);
    setNewCpmk({ kode: '', deskripsi: '' });
    toast.success('CPMK added');
  };

  const handleRemoveCpmk = (kode: string) => {
    setCpmkList(cpmkList.filter(c => c.kode !== kode));
  };

  return (
    <div className="space-y-6">
      <div className="flex items-start space-x-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <FiTarget className="text-blue-500 text-xl mt-0.5" />
        <div>
          <h3 className="font-medium text-blue-900 dark:text-blue-100">Learning Outcomes (CPMK)</h3>
          <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
            Define Course Learning Outcomes. You can add CPMK now or later after RPS is created.
          </p>
        </div>
      </div>

      {/* Available CPL Info */}
      {availableCPL.length > 0 && (
        <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
          <h4 className="text-sm font-medium text-green-900 dark:text-green-100 mb-2">
            Available CPL ({availableCPL.length})
          </h4>
          <div className="flex flex-wrap gap-2">
            {availableCPL.map((cpl) => (
              <span
                key={cpl.id_cpl}
                className="px-2 py-1 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100 text-xs rounded"
              >
                {cpl.kode_cpl}
              </span>
            ))}
          </div>
          <p className="text-xs text-green-700 dark:text-green-300 mt-2">
            You can map CPMK to these CPL after creating the RPS
          </p>
        </div>
      )}

      {/* Add CPMK Form */}
      <div className="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
        <h4 className="font-medium text-gray-900 dark:text-white mb-4">Add New CPMK</h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kode CPMK
            </label>
            <input
              type="text"
              value={newCpmk.kode}
              onChange={(e) => setNewCpmk({ ...newCpmk, kode: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="e.g., CPMK-1"
            />
          </div>
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Deskripsi
            </label>
            <div className="flex space-x-2">
              <input
                type="text"
                value={newCpmk.deskripsi}
                onChange={(e) => setNewCpmk({ ...newCpmk, deskripsi: e.target.value })}
                className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="Enter CPMK description..."
                onKeyPress={(e) => e.key === 'Enter' && handleAddCpmk()}
              />
              <button
                type="button"
                onClick={handleAddCpmk}
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
              >
                Add
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* CPMK List */}
      {cpmkList.length > 0 ? (
        <div>
          <h4 className="font-medium text-gray-900 dark:text-white mb-3">
            CPMK List ({cpmkList.length})
          </h4>
          <div className="space-y-2">
            {cpmkList.map((cpmk) => (
              <div
                key={cpmk.kode}
                className="flex items-start justify-between p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700"
              >
                <div className="flex-1">
                  <div className="font-medium text-gray-900 dark:text-white">
                    {cpmk.kode}
                  </div>
                  <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {cpmk.deskripsi}
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => handleRemoveCpmk(cpmk.kode)}
                  className="ml-4 text-red-600 hover:text-red-800 dark:text-red-400"
                >
                  Remove
                </button>
              </div>
            ))}
          </div>
        </div>
      ) : (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <FiTarget className="mx-auto text-4xl mb-2 opacity-50" />
          <p>No CPMK added yet. You can skip this step and add CPMK later.</p>
        </div>
      )}
    </div>
  );
};

// ============= STEP 4: Review & Submit =============
interface Step4Props {
  formData: CreateRPSData;
  cpmkList: Array<{ kode: string; deskripsi: string }>;
  mataKuliahs: MataKuliah[];
  dosens: Dosen[];
}

const Step4Review: React.FC<Step4Props> = ({ formData, cpmkList, mataKuliahs, dosens }) => {
  const selectedMK = mataKuliahs.find(mk => mk.kode_mk === formData.kode_mk);
  const selectedDosen = dosens.find(d => String(d.id_dosen) === formData.ketua_pengembang);

  return (
    <div className="space-y-6">
      <div className="flex items-start space-x-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
        <FiCheckCircle className="text-green-500 text-xl mt-0.5" />
        <div>
          <h3 className="font-medium text-green-900 dark:text-green-100">Review & Submit</h3>
          <p className="text-sm text-green-700 dark:text-green-300 mt-1">
            Review all information before creating the RPS. Click "Complete" to create as draft.
          </p>
        </div>
      </div>

      {/* Basic Information */}
      <div className="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
        <h4 className="font-semibold text-gray-900 dark:text-white mb-3">Basic Information</h4>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="text-gray-600 dark:text-gray-400">Mata Kuliah:</span>
            <p className="font-medium text-gray-900 dark:text-white">
              {selectedMK?.kode_mk} - {selectedMK?.nama_mk}
            </p>
          </div>
          <div>
            <span className="text-gray-600 dark:text-gray-400">SKS:</span>
            <p className="font-medium text-gray-900 dark:text-white">{selectedMK?.sks}</p>
          </div>
          <div>
            <span className="text-gray-600 dark:text-gray-400">Semester:</span>
            <p className="font-medium text-gray-900 dark:text-white">{formData.semester_berlaku}</p>
          </div>
          <div>
            <span className="text-gray-600 dark:text-gray-400">Tahun Ajaran:</span>
            <p className="font-medium text-gray-900 dark:text-white">{formData.tahun_ajaran}</p>
          </div>
          <div>
            <span className="text-gray-600 dark:text-gray-400">Ketua Pengembang:</span>
            <p className="font-medium text-gray-900 dark:text-white">{selectedDosen?.nama}</p>
          </div>
          <div>
            <span className="text-gray-600 dark:text-gray-400">Tanggal Disusun:</span>
            <p className="font-medium text-gray-900 dark:text-white">{formData.tanggal_disusun}</p>
          </div>
        </div>
      </div>

      {/* Course Description */}
      <div className="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
        <h4 className="font-semibold text-gray-900 dark:text-white mb-3">Course Description</h4>
        <div className="space-y-3 text-sm">
          <div>
            <span className="text-gray-600 dark:text-gray-400">Full Description:</span>
            <p className="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap">
              {formData.deskripsi_mk}
            </p>
          </div>
          <div>
            <span className="text-gray-600 dark:text-gray-400">Brief Description:</span>
            <p className="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap">
              {formData.deskripsi_singkat}
            </p>
          </div>
        </div>
      </div>

      {/* CPMK List */}
      <div className="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
        <h4 className="font-semibold text-gray-900 dark:text-white mb-3">
          Learning Outcomes (CPMK) - {cpmkList.length} items
        </h4>
        {cpmkList.length > 0 ? (
          <div className="space-y-2">
            {cpmkList.map((cpmk) => (
              <div key={cpmk.kode} className="p-2 bg-gray-50 dark:bg-gray-700 rounded">
                <span className="font-medium text-gray-900 dark:text-white">{cpmk.kode}:</span>
                <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">{cpmk.deskripsi}</span>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-gray-500 dark:text-gray-400 italic">
            No CPMK added. You can add them after RPS is created.
          </p>
        )}
      </div>

      <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <p className="text-sm text-yellow-800 dark:text-yellow-200">
          <strong>Note:</strong> RPS will be created as <strong>DRAFT</strong> status.
          You can edit and add more details later before submitting for approval.
        </p>
      </div>
    </div>
  );
};
