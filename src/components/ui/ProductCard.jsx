import React from 'react';

const ProductCard = ({ product, onAddToCart }) => {
  return (
    <div className="bg-white rounded-xl overflow-hidden shadow-md transition-all hover:translate-y-[-5px] hover:shadow-lg flex flex-col h-full">
      <div className="w-full h-48 bg-gray-50">
        <img 
          src={product.image} 
          alt={product.name} 
          className="w-full h-full object-contain p-4"
        />
      </div>
      <div className="p-6 flex flex-col flex-grow">
        <h3 className="text-xl font-semibold text-gray-800 mb-2">{product.name}</h3>
        <p className="text-gray-600 text-sm mb-4">{product.description}</p>
        <div className="bg-gray-50 p-4 rounded-lg mb-4 text-sm">
          <div><strong>Screen Size:</strong> {product.specs.screenSize}</div>
          <div><strong>Flow Rate:</strong> {product.specs.flowRate}</div>
          <div><strong>Weight:</strong> {product.specs.weight}</div>
        </div>
        <button 
          className="w-full bg-purple-700 text-white py-2 px-4 rounded-lg transition-all hover:bg-purple-800 font-medium mt-auto"
          onClick={() => onAddToCart(product.id)}
        >
          Add to Cart
        </button>
      </div>
    </div>
  );
};

export default ProductCard;