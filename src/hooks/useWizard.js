import { useWizardContext } from '../context/WizardContext';

export function useWizard() {
  // Simply return the context - this provides a nice abstraction if we 
  // ever change the underlying implementation
  return useWizardContext();
}