import React from 'react';
import { useWizard } from '../../hooks/useWizard';

const BackButton = () => {
  const { currentStage, previousStage } = useWizard();
  
  // Only show the back button if we're not on the first stage
  if (currentStage === 0) {
    return null;
  }
  
  return (
    <button 
      className="fixed top-8 left-8 bg-white bg-opacity-15 text-white py-3 px-6 rounded-lg transition-all hover:bg-opacity-25 flex items-center gap-2 backdrop-blur-md border border-white border-opacity-10 font-medium"
      onClick={previousStage}
    >
      &lt; Back
    </button>
  );
};

export default BackButton;