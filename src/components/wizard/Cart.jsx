import React, { useState, useRef, createRef } from 'react';
import { useWizard } from '../../hooks/useWizard';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import { submitOrder } from '../../services/api';

const Cart = () => {
  const { cart, removeFromCart, addNewStrainer, setCurrentStage, setCart } = useWizard();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [orderSuccess, setOrderSuccess] = useState(null);
  const [orderError, setOrderError] = useState(null);
  const [showContactForm, setShowContactForm] = useState(false);
  
  // Customer information
  const [customerInfo, setCustomerInfo] = useState({
    name: '',
    company: '',
    email: '',
    phone: ''
  });
  
  // Create refs for each cart item
  const nodeRefs = React.useMemo(() => {
    return cart.map(() => createRef());
  }, [cart]);
  
  // Function to format custom option values for display
  const formatCustomOption = (optionName) => {
    // Check if it's a custom option (starts with "custom:")
    if (typeof optionName === 'string' && optionName.startsWith('custom:')) {
      return optionName.replace('custom:', '');
    }
    return optionName;
  };
  
  // Handle customer info input change
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setCustomerInfo(prev => ({
      ...prev,
      [name]: value
    }));
  };
  
  // Empty cart view
  if (cart.length === 0) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <div className="text-center py-8 text-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <h3 className="text-xl font-semibold mb-2">Your cart is empty</h3>
          <p className="text-gray-500 mb-6">You haven't added any strainers to your cart yet.</p>
          <button 
            className="bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
            onClick={() => setCurrentStage(0)}
          >
            Start Selecting a Strainer
          </button>
        </div>
      </div>
    );
  }
  
  // Handle remove item function
  const handleRemoveItem = (itemId) => {
    console.log('Removing item with ID:', itemId);
    removeFromCart(itemId);
  };
  
  // Show contact form
  const handleRequestQuote = () => {
    setShowContactForm(true);
  };
  
  // Simulate sending an email
  const sendEmailToCompany = async (orderData, customerInfo) => {
    console.log('Sending email to dev@prpl.com.au');
    
    // In a real implementation, this would be an API call to your backend
    return new Promise((resolve) => {
      setTimeout(() => {
        console.log('Email content:', {
          to: 'dev@prpl.com.au',
          subject: `Quotation Request from ${customerInfo.name} at ${customerInfo.company}`,
          body: `
            New quotation request details:
            
            Customer Information:
            Name: ${customerInfo.name}
            Company: ${customerInfo.company}
            Email: ${customerInfo.email}
            Phone: ${customerInfo.phone}
            
            Order Details:
            ${orderData.items.map((item, index) => `
              Item ${index + 1}: ${item.productName}
              ${item.isSpecialOrder ? '(Special Order)' : ''}
              Specifications:
              ${Object.entries(item.selections).map(([key, value]) => `- ${key}: ${value}`).join('\n')}
            `).join('\n\n')}
            
            Timestamp: ${new Date().toLocaleString()}
          `
        });
        
        resolve({
          success: true,
          message: 'Email sent successfully',
        });
      }, 1500);
    });
  };
  
  // Handle form submission
  const handleSubmitOrder = async (e) => {
    if (e) e.preventDefault();
    
    setIsSubmitting(true);
    setOrderSuccess(null);
    setOrderError(null);
    
    try {
      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(customerInfo.email)) {
        throw new Error('Please enter a valid email address');
      }
      
      // Prepare order data
      const orderData = {
        items: cart.map(item => ({
          productId: item.product.id,
          productName: item.product.name,
          isSpecialOrder: item.isSpecialOrder || false,
          selections: item.selections.reduce((acc, sel) => {
            // Format custom options for the API
            const value = sel.optionName;
            acc[sel.stage] = typeof value === 'string' && value.startsWith('custom:') 
              ? value.replace('custom:', '') 
              : value;
            return acc;
          }, {})
        })),
        customer: customerInfo,
        timestamp: new Date().toISOString()
      };
      
      // Submit order to the API
      const orderResponse = await submitOrder(orderData);
      
      // Send email to the company
      const emailResponse = await sendEmailToCompany(orderData, customerInfo);
      
      setOrderSuccess({
        ...orderResponse,
        emailSent: emailResponse.success
      });
      
      // Clear cart after successful submission
      setTimeout(() => {
        setCart([]);
      }, 5000);
      
    } catch (error) {
      setOrderError(error.message || 'Failed to submit order');
    } finally {
      setIsSubmitting(false);
    }
  };
  
  // Customer information form
  if (showContactForm) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <h2 className="text-2xl font-bold text-gray-800 mb-6 text-center">Customer Information</h2>
        
        {orderError && (
          <div className="bg-red-100 text-red-800 p-4 rounded-lg mb-4">
            <p className="font-semibold">Error</p>
            <p>{orderError}</p>
          </div>
        )}
        
        <form onSubmit={handleSubmitOrder}>
          <div className="mb-4">
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Full Name <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="name"
              name="name"
              value={customerInfo.name}
              onChange={handleInputChange}
              className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none"
              placeholder="Enter your full name"
              required
            />
          </div>
          
          <div className="mb-4">
            <label htmlFor="company" className="block text-sm font-medium text-gray-700 mb-1">
              Company Name <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="company"
              name="company"
              value={customerInfo.company}
              onChange={handleInputChange}
              className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none"
              placeholder="Enter your company name"
              required
            />
          </div>
          
          <div className="mb-4">
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
              Email Address <span className="text-red-500">*</span>
            </label>
            <input
              type="email"
              id="email"
              name="email"
              value={customerInfo.email}
              onChange={handleInputChange}
              className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none"
              placeholder="Enter your email address"
              required
            />
          </div>
          
          <div className="mb-6">
            <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
              Phone Number <span className="text-red-500">*</span>
            </label>
            <input
              type="tel"
              id="phone"
              name="phone"
              value={customerInfo.phone}
              onChange={handleInputChange}
              className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-700 focus:outline-none"
              placeholder="Enter your phone number"
              required
            />
          </div>
          
          <div className="flex gap-3">
            <button
              type="button"
              className="flex-1 py-3 px-6 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition-all font-medium"
              onClick={() => setShowContactForm(false)}
              disabled={isSubmitting}
            >
              Back to Cart
            </button>
            
            <button
              type="submit"
              className={`flex-1 py-3 px-6 rounded-lg transition-all font-medium flex items-center justify-center ${
                isSubmitting 
                  ? 'bg-gray-400 text-white cursor-not-allowed' 
                  : 'bg-green-600 text-white hover:bg-green-700'
              }`}
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <>
                  <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                  Processing...
                </>
              ) : (
                'Submit Request'
              )}
            </button>
          </div>
        </form>
      </div>
    );
  }
  
  // Success message after order submission
  if (orderSuccess) {
    return (
      <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
        <div className="text-center py-8">
          <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 className="text-xl font-semibold text-gray-800 mb-2">Quotation Request Submitted!</h3>
          <p className="text-gray-600 mb-2">Your request has been successfully submitted.</p>
          <p className="text-gray-600 mb-2">Order ID: {orderSuccess.orderId}</p>
          <p className="text-gray-600 mb-6">A confirmation has been sent to your email.</p>
          <p className="text-sm text-gray-500 mb-6">
            You will receive a response within {orderSuccess.estimatedResponse}.
          </p>
          <button 
            className="bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
            onClick={() => setCurrentStage(0)}
          >
            Start a New Selection
          </button>
        </div>
      </div>
    );
  }
  
  // Cart with items - NO PRICES anywhere
  return (
    <div className="bg-white rounded-xl p-8 max-w-xl mx-auto shadow-md">
      <h2 className="text-2xl font-bold text-gray-800 mb-6 text-center">Your Cart</h2>
      
      <TransitionGroup component={null}>
        {cart.map((item, index) => (
          <CSSTransition
            key={item.id}
            timeout={300}
            classNames="fade"
            nodeRef={nodeRefs[index]}
          >
            <div ref={nodeRefs[index]} className="bg-white rounded-lg p-6 mb-4 shadow-sm border border-gray-100">
              <div className="flex justify-between items-center mb-4">
                <div className="font-semibold text-purple-700">{item.product.name}</div>
                {item.isSpecialOrder && (
                  <span className="bg-purple-100 text-purple-800 text-xs py-1 px-2 rounded-full">
                    Special Order
                  </span>
                )}
              </div>
              
              {/* Product specifications */}
              <div className="mb-4">
                <h4 className="text-sm font-medium text-gray-500 mb-2">Specifications:</h4>
                <div className="border-t border-gray-100 pt-2">
                  {item.selections.map((selection, idx) => (
                    <div key={idx} className="flex justify-between items-center py-2 border-b border-gray-100">
                      <span className="text-gray-600">{selection.stage}:</span>
                      <span className="font-medium text-gray-800">
                        {formatCustomOption(selection.optionName)}
                        {selection.selection && selection.selection.startsWith('custom:') && (
                          <span className="ml-2 bg-purple-100 text-purple-800 text-xs py-1 px-2 rounded">Custom</span>
                        )}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
              
              <button 
                className="mt-2 bg-red-600 text-white py-1 px-3 rounded text-sm hover:bg-red-700 transition-all"
                onClick={() => handleRemoveItem(item.id)}
              >
                Remove
              </button>
            </div>
          </CSSTransition>
        ))}
      </TransitionGroup>
      
      <div className="flex gap-4 mt-6">
        <button 
          className="flex-1 bg-purple-700 text-white py-3 px-6 rounded-lg transition-all hover:bg-purple-800 font-medium"
          onClick={addNewStrainer}
        >
          Add Another Strainer
        </button>
        <button 
          className="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg transition-all hover:bg-green-700 font-medium"
          onClick={handleRequestQuote}
        >
          Request A Quotation
        </button>
      </div>
    </div>
  );
};

export default Cart;