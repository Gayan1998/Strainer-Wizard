// API Configuration updated to work with PHP backend
export const API_CONFIG = {
  // Base URL for all API requests - change to your backend path
  BASE_URL: 'https://strainerwizard.prpl.com.au/backend',
  //strainerwizard.prpl.com.au
  // Set to true to use mock data for development, false for production API calls
  USE_MOCK_DATA: false,
  
  // Timeout for API requests in milliseconds
  TIMEOUT: 10000,
  
  // Paths for various API endpoints
  ENDPOINTS: {
    PRODUCTS: '/products',
    ORDERS: '/orders',
    EMAIL: '/email',
  },
  
  // Headers to include with all requests
  HEADERS: {
    'Content-Type': 'application/json',
  }
};

/**
 * Function to build a complete API URL
 * @param {string} endpoint - The API endpoint path
 * @returns {string} - The complete URL
 */
export const buildApiUrl = (endpoint) => {
  return `${API_CONFIG.BASE_URL}${endpoint}`;
};

/**
 * Function to determine if mock data should be used
 * @returns {boolean} - True if mock data should be used
 */
export const shouldUseMockData = () => {
  return API_CONFIG.USE_MOCK_DATA;
};