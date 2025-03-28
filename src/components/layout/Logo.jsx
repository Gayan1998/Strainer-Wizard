import React from 'react';

const Logo = () => {
  return (
    <div className="fixed bottom-5 right-5 flex items-center gap-3 text-white bg-white bg-opacity-10 backdrop-blur-md p-3 rounded-lg border border-white border-opacity-10">
      <div className="flex flex-col items-end">

        <div className="flex items-center gap-2">
          <img 
            src="https://prpl.com.au/wp-content/uploads/2018/06/Purple_eng.png" 
            alt="Purple Engineering" 
            className="h-8 object-contain"
          />
          <span className="font-medium">Purple Engineering</span>
        </div>
      </div>
    </div>
  );
};

export default Logo;