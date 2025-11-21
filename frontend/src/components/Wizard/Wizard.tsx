import React, { useState } from 'react';
import type { ReactNode } from 'react';
import { FiCheck, FiChevronLeft, FiChevronRight } from 'react-icons/fi';

export interface WizardStep {
  id: string;
  title: string;
  description?: string;
  component: ReactNode;
  validate?: () => boolean | Promise<boolean>;
}

interface WizardProps {
  steps: WizardStep[];
  onComplete: () => void | Promise<void>;
  onCancel: () => void;
  title: string;
  isSubmitting?: boolean;
}

export const Wizard: React.FC<WizardProps> = ({
  steps,
  onComplete,
  onCancel,
  title,
  isSubmitting = false,
}) => {
  const [currentStep, setCurrentStep] = useState(0);
  const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set());

  const handleNext = async () => {
    const step = steps[currentStep];

    // Validate current step if validation function provided
    if (step.validate) {
      const isValid = await step.validate();
      if (!isValid) return;
    }

    // Mark current step as completed
    setCompletedSteps(prev => new Set([...prev, currentStep]));

    if (currentStep === steps.length - 1) {
      // Last step - complete wizard
      await onComplete();
    } else {
      // Move to next step
      setCurrentStep(currentStep + 1);
    }
  };

  const handlePrevious = () => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleStepClick = (stepIndex: number) => {
    // Allow jumping to previous steps or completed steps
    if (stepIndex < currentStep || completedSteps.has(stepIndex)) {
      setCurrentStep(stepIndex);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">{title}</h2>
        </div>

        {/* Progress Steps */}
        <div className="px-6 pt-6">
          <div className="flex items-center justify-between">
            {steps.map((step, index) => (
              <div key={step.id} className="flex items-center flex-1">
                {/* Step Circle */}
                <div className="flex flex-col items-center">
                  <button
                    onClick={() => handleStepClick(index)}
                    disabled={index > currentStep && !completedSteps.has(index)}
                    className={`
                      w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-all
                      ${
                        index === currentStep
                          ? 'bg-primary-600 text-white ring-4 ring-primary-100 dark:ring-primary-900'
                          : completedSteps.has(index)
                          ? 'bg-green-500 text-white cursor-pointer hover:bg-green-600'
                          : index < currentStep
                          ? 'bg-primary-200 text-primary-700 dark:bg-primary-900 dark:text-primary-300 cursor-pointer hover:bg-primary-300'
                          : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400 cursor-not-allowed'
                      }
                    `}
                  >
                    {completedSteps.has(index) ? (
                      <FiCheck className="text-lg" />
                    ) : (
                      <span>{index + 1}</span>
                    )}
                  </button>
                  <div className="mt-2 text-center">
                    <div
                      className={`text-sm font-medium ${
                        index === currentStep
                          ? 'text-primary-600 dark:text-primary-400'
                          : 'text-gray-600 dark:text-gray-400'
                      }`}
                    >
                      {step.title}
                    </div>
                    {step.description && (
                      <div className="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        {step.description}
                      </div>
                    )}
                  </div>
                </div>

                {/* Connector Line */}
                {index < steps.length - 1 && (
                  <div
                    className={`flex-1 h-1 mx-2 transition-all ${
                      completedSteps.has(index) || index < currentStep
                        ? 'bg-primary-500'
                        : 'bg-gray-200 dark:bg-gray-700'
                    }`}
                  />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Step Content */}
        <div className="flex-1 overflow-y-auto p-6">
          {steps[currentStep].component}
        </div>

        {/* Footer Navigation */}
        <div className="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between">
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            disabled={isSubmitting}
          >
            Cancel
          </button>

          <div className="flex space-x-3">
            {currentStep > 0 && (
              <button
                type="button"
                onClick={handlePrevious}
                className="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                disabled={isSubmitting}
              >
                <FiChevronLeft className="mr-1" />
                Previous
              </button>
            )}

            <button
              type="button"
              onClick={handleNext}
              className="flex items-center px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                'Processing...'
              ) : currentStep === steps.length - 1 ? (
                'Complete'
              ) : (
                <>
                  Next
                  <FiChevronRight className="ml-1" />
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
