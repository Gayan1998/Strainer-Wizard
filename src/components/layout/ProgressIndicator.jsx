import React from 'react';
import { useWizard } from '../../hooks/useWizard';
import { stages } from '../../data/stages';

const ProgressIndicator = () => {
  const { currentStage } = useWizard();
  
  return (
    <div className="fixed bottom-0 left-0 w-full py-6 px-8 bg-white bg-opacity-10 backdrop-blur-md border-t border-white border-opacity-10">
      <div className="flex justify-center gap-3">
        {stages.map((_, index) => (
          <div
            key={index}
            className="relative"
          >
            <div
              className={`w-2 h-2 rounded-full transition-all duration-500 ${
                index === currentStage 
                  ? 'bg-white scale-125' 
                  : index < currentStage 
                    ? 'bg-white' 
                    : 'bg-white bg-opacity-30'
              }`}
            ></div>
            {index === currentStage && (
              <div className="absolute inset-0 bg-white rounded-full animate-ping opacity-50"></div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ProgressIndicator;