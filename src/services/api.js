import { API_CONFIG, buildApiUrl, shouldUseMockData } from '../config/api.config';

/**
 * Fetch products from the API based on selection criteria
 * @param {Object} filters - The filter criteria (type, material, etc.)
 * @returns {Promise} - Promise resolving to the fetched products
 */
export const fetchProducts = async (filters) => {
  try {
    // Add debugging for the filters
    console.log('Fetching products with filters:', filters);
    
    // Build query parameters based on filters
    const queryParams = new URLSearchParams();
    if (filters.type) queryParams.append('type', filters.type);
    if (filters.material) queryParams.append('material', filters.material);
    if (filters.connection) queryParams.append('connection', filters.connection);
    if (filters.size) queryParams.append('size', filters.size);
    if (filters.pressure) queryParams.append('pressure', filters.pressure);
    
    const url = `${buildApiUrl(API_CONFIG.ENDPOINTS.PRODUCTS)}?${queryParams.toString()}`;
    console.log('API URL:', url);
    
    // For development/testing - use mock data if configured
    if (shouldUseMockData()) {
      console.log('Using mock data - in production, this would call:', url);
      console.log('Filters:', filters);
      const products = await getMockProducts(filters);
      
      // Always return an array, even if empty
      return products || [];
    }
    
    // Set up fetch options with timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_CONFIG.TIMEOUT);
    
    const options = {
      method: 'GET',
      headers: API_CONFIG.HEADERS,
      signal: controller.signal,
    };
    
    // Make the API call
    console.log('Sending API request with options:', options);
    const response = await fetch(url, options);
    clearTimeout(timeoutId); // Clear the timeout if request completes
    
    console.log('API response status:', response.status);
    
    if (!response.ok) {
      // Get the response text for better error reporting
      const errorText = await response.text();
      console.error('API error response text:', errorText);
      throw new Error(`API error: ${response.status} ${response.statusText}`);
    }
    
    // Inside your fetchProducts function
    // More resilient response handling
    const data = await response.json();
    console.log('API response data:', data);

    // Handle different response formats
    if (Array.isArray(data)) {
      return data;
    } else if (data.data && Array.isArray(data.data)) {
      return data.data;
    } else if (data.products && Array.isArray(data.products)) {
      return data.products;
    } else {
      console.warn('Unexpected API response format:', data);
      return []; // Return empty array as fallback
    }
  } catch (error) {
    // Handle abort errors specifically
    if (error.name === 'AbortError') {
      throw new Error('Request timed out. Please try again.');
    }
    console.error('Error fetching products:', error);
    
    // Use mock data as fallback if real API fails
    if (API_CONFIG.USE_MOCK_ON_FAILURE) {
      console.log('API call failed, falling back to mock data');
      return getMockProducts(filters);
    }
    
    throw error;
  }
};

/**
 * Submit an order to the API with enhanced error handling
 * @param {Object} orderData - The order data to submit
 * @returns {Promise} - Promise resolving to the order confirmation
 */
export const submitOrder = async (orderData) => {
  try {
    const url = buildApiUrl(API_CONFIG.ENDPOINTS.ORDERS);
    
    if (shouldUseMockData()) {
      console.log('Using mock data - in production, this would call:', url);
      console.log('Order data:', orderData);
      
      // Send mock email
      try {
        await sendEmail(orderData);
      } catch (emailError) {
        console.error('Error sending email (mock):', emailError);
        // Continue with order processing even if email fails
      }
      
      return getMockOrderConfirmation(orderData);
    }
    
    // Set up fetch options
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_CONFIG.TIMEOUT);
    
    const options = {
      method: 'POST',
      headers: API_CONFIG.HEADERS,
      body: JSON.stringify(orderData),
      signal: controller.signal,
    };
    
    console.log('Submitting order to:', url);
    console.log('Order data being sent:', orderData);
    
    const response = await fetch(url, options);
    clearTimeout(timeoutId);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Order API error response:', errorText);
      throw new Error(`API error: ${response.status} ${response.statusText}`);
    }
    
    const data = await response.json();
    
    // Send email after successful order submission
    // But don't fail the overall operation if email sending fails
    try {
      await sendEmail(orderData);
    } catch (emailError) {
      console.error('Error sending order confirmation email:', emailError);
      // Continue despite email error
    }
    
    return data;
  } catch (error) {
    if (error.name === 'AbortError') {
      throw new Error('Request timed out. Please try again.');
    }
    console.error('Error submitting order:', error);
    throw error;
  }
};

/**
 * Send an email with order data to the backend
 * @param {Object} orderData - The order data to include in the email
 * @returns {Promise} - Promise resolving when email is sent
 */
export const sendEmail = async (orderData) => {
  try {
    // For development/testing - use mock data if configured
    if (shouldUseMockData()) {
      console.log('EMAIL WOULD BE SENT (MOCK):', orderData);
      // Simulate API call delay
      return new Promise(resolve => {
        setTimeout(() => {
          resolve({ success: true, message: 'Email sent successfully' });
        }, 500);
      });
    }
    
    // Build email data from order data
    const emailData = {
      to: 'dev@prpl.com.au',
      subject: `Quotation Request from ${orderData.customer.name} at ${orderData.customer.company}`,
      body: formatEmailBody(orderData),
      // Include the raw order data for the server to use if needed
      orderData: orderData
    };
    
    // In production, this would call your email sending API
    const url = buildApiUrl(API_CONFIG.ENDPOINTS.EMAIL);
    
    const options = {
      method: 'POST',
      headers: API_CONFIG.HEADERS,
      body: JSON.stringify(emailData)
    };
    
    console.log('Sending email request to:', url);
    console.log('Email data:', emailData);
    
    const response = await fetch(url, options);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Email API error response:', errorText);
      throw new Error(`Email API error: ${response.status} ${response.statusText}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('Error sending email:', error);
    throw error;
  }
};

/**
 * Format email body based on the updated order data format
 * @param {Object} orderData - The order data
 * @returns {String} - Formatted email body
 */
const formatEmailBody = (orderData) => {
  const customer = orderData.customer || {};
  const items = orderData.items || [];
  
  let emailBody = `
New quotation request details:

Customer Information:
Name: ${customer.name || 'N/A'}
Company: ${customer.company || 'N/A'}
Email: ${customer.email || 'N/A'}
Phone: ${customer.phone || 'N/A'}
`;

  // Add the new delivery address field if present
  if (customer.deliveryAddress) {
    emailBody += `Delivery Address: ${customer.deliveryAddress}\n`;
  }
  
  // Add delivery preference
  emailBody += `Needs Delivery: ${customer.needsDelivery ? 'Yes' : 'No'}\n\n`;

  // Format order items with the new selections structure
  emailBody += `Order Details:
${items.map((item, index) => `
Item ${index + 1}: ${item.productName || 'N/A'} (ID: ${item.productId || 'N/A'})
${item.isSpecialOrder ? '(Special Order)' : ''}
Specifications:
${Object.entries(item.selections || {}).map(([key, value]) => `- ${key}: ${value}`).join('\n')}
`).join('\n')}

Timestamp: ${new Date(orderData.timestamp || Date.now()).toLocaleString()}
  `;
  
  return emailBody;
};

/**
 * Mock function that returns filtered products based on selection criteria
 * This simulates an API call for development/testing
 */
const getMockProducts = (filters) => {
  // Simulate network delay
  return new Promise((resolve) => {
    setTimeout(() => {
      const mockDatabase = [
        {
          id: 'YS-SS-150-05',
          name: 'ACE-YS62-SS',
          type: 'y-strainer',
          material: 'stainless-steel',
          connection: 'flanged',
          size: '0.5',
          pressure: '150',
          description: 'High-performance Y-strainer with superior filtration',
          image: '/api/placeholder/200/200',
          specs: {
            screenSize: '40 mesh',
            flowRate: '20 GPM',
            weight: '5.2 lbs'
          }
        },
        {
          id: 'BS-CS-300-1',
          name: 'BS Pro Basket Strainer',
          type: 'basket-strainer',
          material: 'carbon-steel',
          connection: 'flanged',
          size: '1',
          pressure: '300',
          description: 'Industrial-grade basket strainer for heavy-duty applications',
          image: '/api/placeholder/200/200',
          specs: {
            screenSize: '60 mesh',
            flowRate: '45 GPM',
            weight: '12.8 lbs'
          }
        },
        {
          id: 'DS-CI-150-2',
          name: 'DualFlo Duplex Strainer',
          type: 'duplex-strainer',
          material: 'cast-iron',
          connection: 'threaded',
          size: '2',
          pressure: '150',
          description: 'Continuous flow duplex strainer for non-stop operations',
          image: '/api/placeholder/200/200',
          specs: {
            screenSize: '80 mesh',
            flowRate: '75 GPM',
            weight: '28.5 lbs'
          }
        },
        {
          id: 'YS-CS-600-3',
          name: 'PowerFlow Y-Strainer',
          type: 'y-strainer',
          material: 'carbon-steel',
          connection: 'welded',
          size: '3',
          pressure: '600',
          description: 'Heavy-duty Y-strainer for high-pressure industrial applications',
          image: '/api/placeholder/200/200',
          specs: {
            screenSize: '100 mesh',
            flowRate: '120 GPM',
            weight: '18.4 lbs'
          }
        },
        {
          id: 'BS-SS-300-2',
          name: 'PureFlo Basket Strainer',
          type: 'basket-strainer',
          material: 'stainless-steel',
          connection: 'flanged',
          size: '2',
          pressure: '300',
          description: 'Corrosion-resistant basket strainer for chemical processing',
          image: '/api/placeholder/200/200',
          specs: {
            screenSize: '60 mesh',
            flowRate: '90 GPM',
            weight: '14.5 lbs'
          }
        },
        {
          id: 'DS-SS-150-1',
          name: 'ContinuFlow Duplex',
          type: 'duplex-strainer',
          material: 'stainless-steel',
          connection: 'flanged',
          size: '1',
          pressure: '150',
          description: 'Premium duplex strainer for continuous sanitary applications',
          image: '/api/placeholder/200/200',
          specs: {
            screenSize: '100 mesh',
            flowRate: '40 GPM',
            weight: '16.8 lbs'
          }
        }
      ];
      
      // Filter products based on selection criteria
      const filteredProducts = mockDatabase.filter(product => {
        return (
          (!filters.type || product.type === filters.type) &&
          (!filters.material || product.material === filters.material) &&
          (!filters.connection || product.connection === filters.connection) &&
          (!filters.size || product.size === filters.size) &&
          (!filters.pressure || product.pressure === filters.pressure)
        );
      });
      
      console.log('Found', filteredProducts.length, 'matching products');
      resolve(filteredProducts);
    }, 800); // Simulate network delay of 800ms
  });
};

/**
 * Mock function that returns order confirmation
 * This simulates an API call for development/testing
 */
const getMockOrderConfirmation = (orderData) => {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({
        success: true,
        orderId: `ORD-${Date.now()}`,
        message: 'Your quotation request has been successfully submitted',
        estimatedResponse: '24 hours',
        items: orderData.items,
        customer: orderData.customer,
        timestamp: orderData.timestamp || new Date().toISOString()
      });
    }, 1200); // Simulate longer network delay for order submission
  });
};