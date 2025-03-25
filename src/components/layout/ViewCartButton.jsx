import React, { useRef } from 'react';
import { useWizard } from '../../hooks/useWizard';
import { CSSTransition } from 'react-transition-group';

const ViewCartButton = () => {
  const { cart, currentStage, viewCart } = useWizard();
  const nodeRef = useRef(null);
  
  // Don't show the button if cart is empty or we're already on the cart page
  if (cart.length === 0 || currentStage === 6) {
    return null;
  }
  
  return (
    <CSSTransition
      in={cart.length > 0}
      timeout={150}
      classNames="fade"
      unmountOnExit
      nodeRef={nodeRef}
    >
      <button
        ref={nodeRef}
        onClick={viewCart}
        className="fixed top-8 right-8 bg-white shadow-lg rounded-lg py-2 px-4 flex items-center gap-2 text-purple-700 font-medium hover:bg-gray-100 transition-all duration-200 z-50 border border-white border-opacity-10 backdrop-blur-md btn-hover-effect"
      >
        <span className="bg-purple-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm transition-transform duration-200 hover:scale-110">
          {cart.length}
        </span>
        View Cart
      </button>
    </CSSTransition>
  );
};

export default ViewCartButton;