import { useState } from 'react';
import { FiFilter, FiX, FiSearch, FiDownload } from 'react-icons/fi';

export interface FilterField {
  name: string;
  label: string;
  type: 'text' | 'select' | 'date' | 'number';
  options?: Array<{ value: string | number; label: string }>;
  placeholder?: string;
}

interface AdvancedFilterProps {
  fields: FilterField[];
  onFilterChange: (filters: Record<string, any>) => void;
  onExport?: () => void;
  showExport?: boolean;
}

export const AdvancedFilter: React.FC<AdvancedFilterProps> = ({
  fields,
  onFilterChange,
  onExport,
  showExport = false,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [filters, setFilters] = useState<Record<string, any>>({});
  const [searchTerm, setSearchTerm] = useState('');

  const handleFilterChange = (name: string, value: any) => {
    const newFilters = { ...filters, [name]: value };
    setFilters(newFilters);
    onFilterChange(newFilters);
  };

  const handleReset = () => {
    setFilters({});
    setSearchTerm('');
    onFilterChange({});
  };

  const activeFilterCount = Object.values(filters).filter(
    (v) => v !== '' && v !== undefined && v !== null
  ).length + (searchTerm ? 1 : 0);

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
      {/* Header */}
      <div className="flex flex-col sm:flex-row gap-4 mb-4">
        {/* Search */}
        <div className="flex-1 relative">
          <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => {
              setSearchTerm(e.target.value);
              handleFilterChange('search', e.target.value);
            }}
            placeholder="Search..."
            className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
          />
        </div>

        {/* Filter Toggle */}
        <button
          onClick={() => setIsOpen(!isOpen)}
          className="flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          <FiFilter />
          <span className="hidden sm:inline">Filters</span>
          {activeFilterCount > 0 && (
            <span className="bg-primary-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
              {activeFilterCount}
            </span>
          )}
        </button>

        {/* Export Button */}
        {showExport && onExport && (
          <button
            onClick={onExport}
            className="flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
          >
            <FiDownload />
            <span className="hidden sm:inline">Export</span>
          </button>
        )}
      </div>

      {/* Filter Fields */}
      {isOpen && (
        <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {fields.map((field) => (
              <div key={field.name}>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  {field.label}
                </label>
                {field.type === 'select' ? (
                  <select
                    value={filters[field.name] || ''}
                    onChange={(e) => handleFilterChange(field.name, e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="">All</option>
                    {field.options?.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                ) : (
                  <input
                    type={field.type}
                    value={filters[field.name] || ''}
                    onChange={(e) => handleFilterChange(field.name, e.target.value)}
                    placeholder={field.placeholder}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                  />
                )}
              </div>
            ))}
          </div>

          {/* Reset Button */}
          <div className="flex justify-end">
            <button
              onClick={handleReset}
              className="flex items-center gap-2 px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            >
              <FiX />
              Reset Filters
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
