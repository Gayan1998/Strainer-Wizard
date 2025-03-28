import React, { useState, useRef } from 'react';
import { useWizard } from '../../hooks/useWizard';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

const CardSelection = ({ stage, stageIndex }) => {
  const { selections, selectOption } = useWizard();
  const [selectedCard, setSelectedCard] = useState(null);
  const [showCustomInput, setShowCustomInput] = useState(false);
  const [customValue, setCustomValue] = useState('');
  const transitionRef = useRef(null);
  const inputRef = useRef(null);
  
  // If first stage (strainer type), don't show custom option
  const isFirstStage = stageIndex === 0;
  
  // Check if this is the materials stage
  const isMaterialsStage = stageIndex === 1;
  
  // Get fixed grid column classes based on stage configuration
  const getGridColClass = () => {
    // Use explicit column classes that Tailwind recognizes
    switch(stage.columns) {
      case 2:
        return "grid-cols-1 md:grid-cols-2";
      case 3:
        return "grid-cols-1 md:grid-cols-3";
      case 4:
        return "grid-cols-1 sm:grid-cols-2 md:grid-cols-4";
      default:
        return "grid-cols-1 md:grid-cols-3";
    }
  };
  
  const handleCardSelect = (optionId) => {
    // If "custom" option is selected, show the input field
    if (optionId === 'custom') {
      setShowCustomInput(true);
      // Focus the input field after a short delay to allow rendering
      setTimeout(() => {
        if (inputRef.current) {
          inputRef.current.focus();
        }
      }, 50);
      return;
    }
    
    setSelectedCard(optionId);
    
    // Add a small delay to allow the animation to play before moving to next stage
    setTimeout(() => {
      selectOption(optionId, stageIndex);
    }, 200);
  };
  
  const handleCustomSubmit = (e) => {
    e.preventDefault();
    if (customValue.trim()) {
      // Use a special format for custom values: custom:{value}
      const customOptionId = `custom:${customValue.trim()}`;
      setSelectedCard(customOptionId);
      
      setTimeout(() => {
        selectOption(customOptionId, stageIndex);
        setShowCustomInput(false);
        setCustomValue('');
      }, 200);
    }
  };
  
  // If showing custom input field
  if (showCustomInput) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">Enter Custom {stage.title.replace('Choose A ', '').replace('Select ', '')}</h3>
        <form onSubmit={handleCustomSubmit}>
          <input
            ref={inputRef}
            type="text"
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none mb-4"
            placeholder={`Enter custom ${stage.title.toLowerCase().replace('choose a ', '').replace('select ', '')}`}
            value={customValue}
            onChange={(e) => setCustomValue(e.target.value)}
          />
          <div className="flex gap-3">
            <button
              type="button"
              className="flex-1 bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition-all"
              onClick={() => setShowCustomInput(false)}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex-1 bg-purple-700 text-white py-2 px-4 rounded-lg hover:bg-purple-800 transition-all"
              disabled={!customValue.trim()}
            >
              Continue
            </button>
          </div>
        </form>
      </div>
    );
  }
  
  return (
    <TransitionGroup component={null}>
      <CSSTransition 
        timeout={250} 
        classNames="card"
        nodeRef={transitionRef}
      >
        <div ref={transitionRef} className={`grid ${getGridColClass()} gap-6`}>
          {/* Regular options */}
          {stage.options.map((option) => (
            <div key={option.id}>
              <div 
                className={`bg-white rounded-xl p-6 text-center transition-all duration-200 cursor-pointer hover:translate-y-[-5px] hover:shadow-lg flex flex-col items-center ${
                  selections.current[stageIndex] === option.id || selectedCard === option.id 
                    ? 'border-4 border-purple-700 selection-pulse' 
                    : 'border-2 border-transparent'
                }`}
                onClick={() => handleCardSelect(option.id)}
              >
                <img 
                  src={option.image} 
                  alt={option.name} 
                  className="w-36 h-36 object-contain mb-5 transition-transform duration-200"
                />
                <h2 className="text-xl font-semibold text-gray-800">{option.name}</h2>
                
                {/* Display description for material options */}
                {isMaterialsStage && option.description && (
                  <p className="text-sm text-gray-600 mt-2">{option.description}</p>
                )}
              </div>
            </div>
          ))}
          
          {/* Custom option - don't show for first stage (strainer type) */}
          {!isFirstStage && (
            <div>
              <div 
                className={`bg-white rounded-xl p-6 text-center transition-all duration-200 cursor-pointer hover:translate-y-[-5px] hover:shadow-lg flex flex-col items-center border-2 border-dashed border-gray-300 hover:border-purple-700`}
                onClick={() => handleCardSelect('custom')}
              >
                <div className="w-36 h-36 flex items-center justify-center mb-5">
                  <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                  </div>
                </div>
                <h2 className="text-xl font-semibold text-gray-800">Custom Option</h2>
              </div>
            </div>
          )}
        </div>
      </CSSTransition>
    </TransitionGroup>
  );
};

export default CardSelection;