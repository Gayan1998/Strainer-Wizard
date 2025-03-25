import React, { useEffect, useState } from 'react';
import { useWizard } from '../../hooks/useWizard';
import ProductCard from '../ui/ProductCard';
import { stages } from '../../data/stages';

const ProductsStage = () => {
  const { 
    findMatchingProducts, 
    addProductToCart,
    addSpecialOrderToCart, 
    resetAll, 
    viewCart, 
    isLoading: contextLoading, 
    error: contextError,
    hasFetched,
    selections
  } = useWizard();
  
  const [localProducts, setLocalProducts] = useState([]);
  const [isLocalLoading, setIsLocalLoading] = useState(true);
  const [localError, setLocalError] = useState(null);
  const [fetchCompleted, setFetchCompleted] = useState(false);
  
  // Check if any custom options were selected
  const hasCustomSelections = Object.values(selections.current).some(
    value => typeof value === 'string' && value.startsWith('custom:')
  );
  
  // Extract custom values for display
  const getCustomOptionsDisplay = () => {
    return Object.entries(selections.current)
      .filter(([_, value]) => typeof value === 'string' && value.startsWith('custom:'))
      .map(([key, value]) => {
        const stageIndex = parseInt(key);
        const stageTitle = stageIndex >= 0 && stageIndex < stages.length 
          ? stages[stageIndex].title.replace('Select ', '').replace('Choose A ', '') 
          : 'Option';
        return {
          stageTitle,
          value: value.replace('custom:', '')
        };
      });
  };
  
  // FIXED: Simplified fetch logic to ensure products are loaded properly
  useEffect(() => {
    let isMounted = true;
    
    // Always attempt to fetch when component mounts
    async function loadProducts() {
      if (fetchCompleted) return; // Prevent multiple fetches
      
      setIsLocalLoading(true);
      setLocalError(null);
      
      try {
        // Start product fetch with current selections
        
        const fetchedProducts = await findMatchingProducts();
        
        // Only update state if component is still mounted
        if (isMounted) {

          setLocalProducts(fetchedProducts || []);
          setFetchCompleted(true);
        }
      } catch (err) {
        if (isMounted) {
          console.error('ProductsStage: Error loading products:', err);
          setLocalError(err.message);
          setFetchCompleted(true);
        }
      } finally {
        if (isMounted) {
          setIsLocalLoading(false);
        }
      }
    }
    
    loadProducts();
    
    // Clean up function to handle component unmounting
    return () => {
      isMounted = false;
    };
  }, [findMatchingProducts]); // FIXED: Removed fetchCompleted and hasFetched dependencies
  
  // Handle special order submission
  const handleRequestSpecialOrder = () => {
    // Add special order to cart with custom values (no price)
    addSpecialOrderToCart();
    
    // Show confirmation and go to cart
    setTimeout(() => {
      viewCart();
    }, 500);
  };
  
  // Determine current state
  const error = localError || contextError;
  const isLoading = isLocalLoading || (contextLoading && !fetchCompleted);
  

  
  // Loading state
  if (isLoading) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <div className="flex flex-col items-center justify-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-700 mb-4"></div>
          <p className="text-gray-600">Loading products...</p>
        </div>
      </div>
    );
  }
  
  // Error state
  if (error) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <div className="bg-red-100 text-red-800 p-4 rounded-lg mb-4">
          <p className="font-semibold">Error loading products</p>
          <p>{error}</p>
        </div>
        <button 
          className="w-full bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
          onClick={resetAll}
        >
          Start Over
        </button>
      </div>
    );
  }

  // Empty products state but with custom selections - show special order option
  if (fetchCompleted && localProducts.length === 0 && hasCustomSelections) {
    const customOptions = getCustomOptionsDisplay();
    
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <div className="bg-amber-100 text-amber-800 p-4 rounded-lg mb-6">
          <p className="font-semibold">No matching products found</p>
          <p>We don't have this exact configuration in stock, but we can special order it for you.</p>
        </div>
        
        <div className="mb-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-3">Your Custom Configuration</h3>
          {customOptions.map((option, index) => (
            <div key={index} className="flex justify-between mb-2 pb-2 border-b border-gray-100">
              <span className="text-gray-700">{option.stageTitle}:</span>
              <span className="font-medium">{option.value}</span>
            </div>
          ))}
        </div>
        
        <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 mb-6 text-center">
          <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <h3 className="text-xl font-semibold text-gray-800 mb-3">Special Order Available</h3>
          <p className="text-gray-600 mb-6">
            Your custom configuration requires a special order. Our team will source this item specifically for you.
          </p>
          <button 
            className="bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
            onClick={handleRequestSpecialOrder}
          >
            Request Special Order
          </button>
        </div>
        
        <div className="text-center">
          <button 
            className="bg-gray-600 text-white py-3 px-6 rounded-lg transition-all hover:bg-gray-700 font-medium"
            onClick={resetAll}
          >
            Select Different Options
          </button>
        </div>
      </div>
    );
  }

  // Empty products state - only show after loading is complete
  if (fetchCompleted && localProducts.length === 0) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <div className="bg-amber-100 text-amber-800 p-4 rounded-lg mb-4">
          <p className="font-semibold">No products found</p>
          <p>No products match your selected criteria. Please adjust your selections.</p>
        </div>
        <button 
          className="w-full bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
          onClick={resetAll}
        >
          Start Over
        </button>
      </div>
    );
  }

  // Products display - render when we have products
  return (
    <div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {localProducts.map((product) => (
          <ProductCard 
            key={product.id} 
            product={product} 
            onAddToCart={addProductToCart}
          />
        ))}
      </div>
      
      
      <div className="mt-8 text-center">
        <button 
          className="bg-gray-600 text-white py-3 px-6 rounded-lg transition-all hover:bg-gray-700 font-medium mr-4"
          onClick={resetAll}
        >
          Select Different Options
        </button>
        <button 
          className="bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
          onClick={viewCart}
        >
          View Cart
        </button>
      </div>
    </div>
  );
};

export default ProductsStage;