import React, { useEffect, useState, useRef } from 'react';
import { CSSTransition, SwitchTransition } from 'react-transition-group';
import { WizardProvider } from './context/WizardContext';
import BackButton from './components/layout/BackButton';
import ProgressIndicator from './components/layout/ProgressIndicator';
import Logo from './components/layout/Logo';
import ViewCartButton from './components/layout/ViewCartButton';
import CardSelection from './components/wizard/CardSelection';
import DropdownSelection from './components/wizard/DropdownSelection';
import ProductsStage from './components/wizard/ProductsStage';
import Cart from './components/wizard/Cart';
import { stages } from './data/stages';
import { useWizard } from './hooks/useWizard';
import './transitions.css';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const WizardContent = () => {
  const { currentStage, cart } = useWizard();
  const [showContent, setShowContent] = useState(true);
  const titleNodeRef = useRef(null);
  const contentNodeRef = useRef(null);
  
  // This ensures the transition works when the currentStage changes
  useEffect(() => {
    setShowContent(true);
  }, [currentStage]);
  
  // Get the current stage title safely
  const getStageTitle = () => {
    // If we're in the cart view (stage 6)
    if (currentStage === 6) {
      return "Shopping Cart";
    }
    
    // Make sure we're accessing a valid stage
    if (currentStage >= 0 && currentStage < stages.length) {
      return stages[currentStage].title;
    }
    
    // Fallback title if something goes wrong
    return "Strainer Selection";
  };
  
  // Render content based on current stage
  const renderContent = () => {
    // If we're in the cart view (stage 6), show the cart
    if (currentStage === 6) {
      return <Cart />;
    }
    
    // Safety check to make sure currentStage is valid
    if (currentStage < 0 || currentStage >= stages.length) {
      return null;
    }
    
    const stage = stages[currentStage];

    // Render different components based on stage type
    if (stage.type === 'cards') {
      return <CardSelection stage={stage} stageIndex={currentStage} />;
    } else if (stage.type === 'dropdown') {
      return <DropdownSelection stage={stage} stageIndex={currentStage} />;
    } else if (stage.type === 'products') {
      return <ProductsStage />;
    }
    
    return null;
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-800 to-purple-500 pt-6 pb-16 px-8 font-sans flex flex-col">
      <BackButton />
      <ViewCartButton />
      
      <div className="container mx-auto max-w-5xl flex-grow flex flex-col">
        <SwitchTransition mode="out-in">
          <CSSTransition
            key={currentStage}
            timeout={200}
            classNames="fade"
            unmountOnExit
            nodeRef={titleNodeRef}
          >
            <h1 ref={titleNodeRef} className="text-4xl font-bold text-white text-center mb-6 mt-6 text-shadow">
              {getStageTitle()}
            </h1>
          </CSSTransition>
        </SwitchTransition>
        
        <div className="flex flex-col mt-2">
          <SwitchTransition mode="out-in">
            <CSSTransition
              key={currentStage}
              timeout={200}
              classNames="fade"
              unmountOnExit
              nodeRef={contentNodeRef}
            >
              <div ref={contentNodeRef}>{renderContent()}</div>
            </CSSTransition>
          </SwitchTransition>
        </div>
      </div>
      
      <ProgressIndicator />
      <Logo />
    </div>
  );
};

const App = () => {
  return (
    <WizardProvider>
      <WizardContent />
      <ToastContainer position="top-right" autoClose={3000} />
    </WizardProvider>
  );
};

export default App;