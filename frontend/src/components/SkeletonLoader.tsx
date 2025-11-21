import React from 'react';

interface SkeletonProps {
  className?: string;
  count?: number;
}

export const Skeleton: React.FC<SkeletonProps> = ({ className = '', count = 1 }) => {
  return (
    <>
      {Array.from({ length: count }).map((_, index) => (
        <div
          key={index}
          className={`animate-pulse bg-gray-200 dark:bg-gray-700 rounded ${className}`}
          aria-hidden="true"
        />
      ))}
    </>
  );
};

export const SkeletonCard: React.FC = () => {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <Skeleton className="h-6 w-1/3 mb-4" />
      <Skeleton className="h-4 w-full mb-2" />
      <Skeleton className="h-4 w-2/3" />
    </div>
  );
};

export const SkeletonTable: React.FC<{ rows?: number; columns?: number }> = ({
  rows = 5,
  columns = 4
}) => {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      {/* Table Header */}
      <div className="bg-gray-50 dark:bg-gray-900 px-6 py-3 border-b border-gray-200 dark:border-gray-700">
        <div className="flex gap-4">
          {Array.from({ length: columns }).map((_, i) => (
            <Skeleton key={i} className="h-4 flex-1" />
          ))}
        </div>
      </div>

      {/* Table Rows */}
      <div className="divide-y divide-gray-200 dark:divide-gray-700">
        {Array.from({ length: rows }).map((_, rowIndex) => (
          <div key={rowIndex} className="px-6 py-4">
            <div className="flex gap-4">
              {Array.from({ length: columns }).map((_, colIndex) => (
                <Skeleton key={colIndex} className="h-4 flex-1" />
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export const SkeletonList: React.FC<{ items?: number }> = ({ items = 5 }) => {
  return (
    <div className="space-y-4">
      {Array.from({ length: items }).map((_, index) => (
        <div
          key={index}
          className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
        >
          <Skeleton className="h-5 w-1/4 mb-3" />
          <Skeleton className="h-4 w-full mb-2" />
          <Skeleton className="h-4 w-3/4" />
        </div>
      ))}
    </div>
  );
};

// Unified Skeleton Loader with variant support
interface SkeletonLoaderProps {
  variant?: 'base' | 'card' | 'table' | 'list';
  rows?: number;
  columns?: number;
  items?: number;
  className?: string;
}

export const SkeletonLoader: React.FC<SkeletonLoaderProps> = ({
  variant = 'base',
  rows,
  columns,
  items,
  className,
}) => {
  switch (variant) {
    case 'card':
      return <SkeletonCard />;
    case 'table':
      return <SkeletonTable rows={rows} columns={columns} />;
    case 'list':
      return <SkeletonList items={items} />;
    default:
      return <Skeleton className={className || "h-4 w-full"} />;
  }
};
