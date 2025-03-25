import React, { useState, useRef } from 'react';
import { useWizard } from '../../hooks/useWizard';

const DropdownSelection = ({ stage, stageIndex }) => {
  const { selections, selectOption } = useWizard();
  const [showCustomInput, setShowCustomInput] = useState(false);
  const [customValue, setCustomValue] = useState('');
  const inputRef = useRef(null);
  
  const handleSelectChange = (e) => {
    const value = e.target.value;
    
    // If "custom" option is selected, show the input field
    if (value === 'custom') {
      setShowCustomInput(true);
      // Focus the input field after a short delay to allow rendering
      setTimeout(() => {
        if (inputRef.current) {
          inputRef.current.focus();
        }
      }, 50);
      return;
    }
    
    // Otherwise, select the option and move to next stage
    selectOption(value, stageIndex);
  };
  
  const handleCustomSubmit = (e) => {
    e.preventDefault();
    if (customValue.trim()) {
      // Use a special format for custom values: custom:{value}
      const customOptionId = `custom:${customValue.trim()}`;
      
      selectOption(customOptionId, stageIndex);
      setShowCustomInput(false);
      setCustomValue('');
    }
  };
  
  // If showing custom input field
  if (showCustomInput) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">Enter Custom {stage.title.replace('Select ', '')}</h3>
        <form onSubmit={handleCustomSubmit}>
          <input
            ref={inputRef}
            type="text"
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none mb-4"
            placeholder={`Enter custom ${stage.title.toLowerCase().replace('select ', '')}`}
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
    <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
      <select 
        className="w-full p-3 text-base border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none transition-all bg-gray-50"
        value={selections.current[stageIndex] || ''}
        onChange={handleSelectChange}
      >
        <option value="" disabled>Select an option</option>
        {stage.options.map((option) => (
          <option key={option.id} value={option.id}>
            {option.name}
          </option>
        ))}
        <option value="custom">Custom Option</option>
      </select>
    </div>
  );
};

export default DropdownSelection;