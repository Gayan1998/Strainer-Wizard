// Wizard stages configuration
export const stages = [
    {
      title: 'Select A Strainer Type',
      type: 'cards',
      columns: 3,
      options: [
        { id: 'y-strainer', name: 'Y Strainer', image: 'https://strainer.net.au/wp-content/uploads/2024/02/Strainer-YS62-SS.jpg' },
        { id: 'basket-strainer', name: 'Basket Strainer', image: 'https://strainer.net.au/wp-content/uploads/2024/02/ACE-BS-150-Strainer-1.jpg' },
        { id: 'duplex-strainer', name: 'Duplex Strainer', image: 'https://strainer.net.au/wp-content/uploads/2024/02/Strainer-DS696-696.jpg' }
      ]
    },
    {
      title: 'Choose A Material of Construction',
      type: 'cards',
      columns: 3,
      options: [
        { id: 'stainless-steel', name: 'Stainless Steel', image: 'https://media.istockphoto.com/id/484048852/photo/metal-cube.jpg?s=612x612&w=0&k=20&c=aOQa80TqZfKgJR1_CgfsT8XL-0TUvnfWhoMrzxrzk2I=' },
        { id: 'carbon-steel', name: 'Carbon Steel', image: '/api/placeholder/150/150' },
        { id: 'cast-iron', name: 'Cast Iron', image: '/api/placeholder/150/150' }
      ]
    },
    {
      title: 'Select End Connection',
      type: 'cards',
      columns: 4,
      options: [
        { id: 'flanged', name: 'Flanged', image: '/api/placeholder/150/150' },
        { id: 'threaded', name: 'Threaded', image: '/api/placeholder/150/150' },
        { id: 'welded', name: 'Welded', image: '/api/placeholder/150/150' },
        { id: 'grooved', name: 'Grooved', image: '/api/placeholder/150/150' }
      ]
    },
    {
      title: 'Select Size',
      type: 'dropdown',
      options: [
        { id: '0.5', name: '1/2"' },
        { id: '0.75', name: '3/4"' },
        { id: '1', name: '1"' },
        { id: '1.5', name: '1-1/2"' },
        { id: '2', name: '2"' },
        { id: '3', name: '3"' },
        { id: '4', name: '4"' }
      ]
    },
    {
      title: 'Select Pressure Rating',
      type: 'dropdown',
      options: [
        { id: '150', name: '150# (PN20)' },
        { id: '300', name: '300# (PN50)' },
        { id: '600', name: '600# (PN100)' },
        { id: '900', name: '900# (PN150)' }
      ]
    },
    {
      title: 'Available Products',
      type: 'products',
      options: []
    }
  ];