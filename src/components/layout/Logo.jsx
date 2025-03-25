import React from 'react';

const Logo = () => {
  return (
    <div className="fixed bottom-5 right-5 flex items-center gap-3 text-white bg-white bg-opacity-10 backdrop-blur-md p-3 rounded-lg border border-white border-opacity-10">
      <div className="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
        <span className="text-purple-700 font-bold">PE</span>
      </div>
      <span className="font-medium">Purple Engineering</span>
    </div>
  );
};

export default Logo;