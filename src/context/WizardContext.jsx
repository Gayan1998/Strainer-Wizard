import React, { createContext, useState, useContext, useEffect, useCallback } from 'react';
import { stages } from '../data/stages';
import { fetchProducts } from '../services/api';
import { toast } from 'react-toastify'; // Import toast from react-toastify

// Create the context with named export
export const WizardContext = createContext();

// Custom hook with named export
export function useWizardContext() {
  const context = useContext(WizardContext);
  if (!context) {
    throw new Error('useWizardContext must be used within a WizardProvider');
  }
  return context;
}

// Provider component with named export
export function WizardProvider({ children }) {
  const [currentStage, setCurrentStage] = useState(0);
  const [selections, setSelections] = useState({
    current: {},
    history: [],
    lastStageReached: 0
  });
  const [cart, setCart] = useState([]);
  const [isTransitioning, setIsTransitioning] = useState(false);
  
  // State for API data
  const [products, setProducts] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [hasFetched, setHasFetched] = useState(false);
  
  // Save selection for the current stage
  const saveSelection = (stageIndex, optionId) => {
    const newSelections = { ...selections };
    newSelections.current[stageIndex] = optionId;
    
    // Handle custom options specially
    let optionName;
    if (optionId.startsWith('custom:')) {
      // For custom options, use the value after 'custom:' as the name
      optionName = optionId;
    } else {
      // For standard options, find the name in the options array
      const selectedOption = stages[stageIndex].options.find(opt => opt.id === optionId);
      optionName = selectedOption?.name;
    }
    
    newSelections.history[stageIndex] = {
      stage: stages[stageIndex].title,
      selection: optionId,
      optionName: optionName,
      timestamp: new Date().toISOString()
    };

    if (stageIndex < newSelections.lastStageReached) {
      newSelections.history = newSelections.history.slice(0, stageIndex + 1);
      for (let i = stageIndex + 1; i <= newSelections.lastStageReached; i++) {
        delete newSelections.current[i];
      }
    }

    newSelections.lastStageReached = Math.max(newSelections.lastStageReached, stageIndex);
    setSelections(newSelections);
  };

  // Handle option selection with animation timing
  const selectOption = (optionId, stageIndex) => {
    if (isTransitioning) return;
    
    setIsTransitioning(true);
    saveSelection(stageIndex, optionId);
    
    setTimeout(() => {
      if (currentStage < stages.length - 1) {
        setCurrentStage(currentStage + 1);
      }
      setIsTransitioning(false);
    }, 150);
  };

  // Handle going back a stage
  const previousStage = () => {
    if (isTransitioning) return;
    if (currentStage > 0) {
      setIsTransitioning(true);
      setTimeout(() => {
        setCurrentStage(currentStage - 1);
        setIsTransitioning(false);
      }, 100);
    }
  };

  // Reset wizard state
  const resetWizard = () => {
    setIsTransitioning(true);
    setTimeout(() => {
      setSelections({
        current: {},
        history: [],
        lastStageReached: 0
      });
      setCurrentStage(0);
      setIsTransitioning(false);
    }, 150);
  };

  // Reset everything
  const resetAll = () => {
    setIsTransitioning(true);
    setTimeout(() => {
      resetWizard();
      setIsTransitioning(false);
    }, 150);
  };

  // Find matching products via API based on current selections - with useCallback
  // FIXED: Removed 'products' from dependency array to avoid circular reference
  const findMatchingProducts = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      // Create filters object from current selections
      const filters = {
        type: selections.current[0],
        material: selections.current[1],
        connection: selections.current[2],
        size: selections.current[3],
        pressure: selections.current[4]
      };
      
      // Add detailed logging to debug filter values
      console.log('Selection values:', selections.current);
      console.log('Filters being sent to API:', filters);
      
      // Fetch products from API
      const fetchedProducts = await fetchProducts(filters);
      console.log('API Response:', fetchedProducts);
      
      // Update state with fetched products
      setProducts(fetchedProducts);
      setHasFetched(true);
      
      return fetchedProducts;
    } catch (err) {
      console.error('Error fetching products:', err);
      setError(err.message || 'Failed to fetch products');
      toast.error('Error fetching products: ' + (err.message || 'Failed to fetch products'));
      setHasFetched(true);
      return [];
    } finally {
      setIsLoading(false);
    }
  }, [selections.current]); // FIXED: Removed hasFetched and products from dependencies

  // Add product to cart with animation timing
  const addProductToCart = (productId) => {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const cartItem = {
      id: Date.now(), // Ensure unique ID using timestamp
      product: product,
      selections: [...selections.history],
      timestamp: new Date().toISOString(),
      isSpecialOrder: false
    };

    setCart(prevCart => [...prevCart, cartItem]);
    
    // Show success message with toast instead of alert
    toast.success('Product has been added to your cart.');
  };
  
  // Add special order to cart without price
  const addSpecialOrderToCart = () => {
    // Format option names for display - replace "custom:" prefix with actual value
    const formattedSelections = selections.history.map(selection => {
      return {
        ...selection,
        optionName: typeof selection.optionName === 'string' && selection.optionName.startsWith('custom:') 
          ? selection.optionName.replace('custom:', '') 
          : selection.optionName
      };
    });
    
    // Create a special product object
    const specialProduct = {
      id: `special-${Date.now()}`,
      name: 'Custom Strainer (Special Order)',
      description: 'Custom configuration strainer that will be special ordered',
      image: '/api/placeholder/200/200',
      specs: {
        note: 'Custom specifications as selected'
      }
    };
    
    const cartItem = {
      id: Date.now(), // Ensure unique ID using timestamp
      product: specialProduct,
      selections: formattedSelections,
      timestamp: new Date().toISOString(),
      isSpecialOrder: true
    };

    setCart(prevCart => [...prevCart, cartItem]);
    
    // Show success message with toast instead of alert
    toast.success('Special order has been added to your cart.');
  };

  // Remove item from cart - no redirection
  const removeFromCart = (itemId) => {
    console.log('Context removeFromCart called with ID:', itemId);
    
    // Use the functional update pattern to ensure we have the latest state
    setCart(prevCart => {
      const updatedCart = prevCart.filter(item => item.id !== itemId);
      return updatedCart;
    });
    
    // Show removal notification
    toast.info('Item removed from cart.');
  };

  // Add new strainer (after completing one)
  const addNewStrainer = () => {
    setIsTransitioning(true);
    setTimeout(() => {
      resetWizard();
      setIsTransitioning(false);
      toast.info('Started configuring a new strainer.');
    }, 150);
  };

  // Toggle cart view with animation timing
  const viewCart = () => {
    setIsTransitioning(true);
    setTimeout(() => {
      setCurrentStage(6);
      setIsTransitioning(false);
    }, 150);
  };
  
  // FIXED: Reset fetch state when changing selection or entering products stage
  useEffect(() => {
    // Reset hasFetched when selection changes or when entering products page
    if (currentStage === 5) { // The products stage (index 5)
      setHasFetched(false);
      setProducts([]); // Clear previous products
    }
  }, [currentStage, selections.current]);

  // Create an object with all the values/functions to provide
  const contextValue = {
    currentStage,
    setCurrentStage,
    selections,
    cart,
    setCart, // Make cart setter available for cart operations
    isTransitioning,
    selectOption,
    previousStage,
    resetWizard,
    resetAll,
    findMatchingProducts,
    addProductToCart,
    addSpecialOrderToCart,
    removeFromCart,
    addNewStrainer,
    viewCart,
    // API related states
    products,
    isLoading,
    error,
    hasFetched,
  };

  return (
    <WizardContext.Provider value={contextValue}>
      {children}
    </WizardContext.Provider>
  );
}