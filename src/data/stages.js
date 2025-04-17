// Wizard stages configuration
export const stages = [
    {
      title: 'Select A Strainer Type',
      type: 'cards',
      columns: 3,
      options: [
        { id: 'Y-Strainer', name: 'Y Strainer', image: 'https://strainer.net.au/wp-content/uploads/2024/02/Strainer-YS62-SS.jpg' },
        { id: 'Basket Strainer', name: 'Basket Strainer', image: 'https://strainer.net.au/wp-content/uploads/2024/02/ACE-BS-150-Strainer-1.jpg' },
        { id: 'Duplex Strainer', name: 'Duplex Strainer', image: 'https://strainer.net.au/wp-content/uploads/2024/02/Strainer-DS696-696.jpg' }
      ]
    },
    {
      title: 'Choose A Material of Construction',
      type: 'cards',
      columns: 3,
      options: [
        { 
          id: 'Stainless Steel', 
          name: 'Stainless Steel', 
          image: 'https://imgur.com/djXtYGx.jpg',
          description: 'Corrosion resistant and suitable for food, pharmaceutical, and chemical applications. Ideal for aggressive media.'
        },
        { 
          id: 'Carbon Steel', 
          name: 'Carbon Steel', 
          image: 'https://imgur.com/pJTXqga.jpg',
          description: 'Cost-effective and strong with good thermal conductivity. Best for non-corrosive applications and high-temperature service.'
        },
        { 
          id: 'Cast Iron', 
          name: 'Cast Iron', 
          image: 'https://imgur.com/hGeTcri.jpg',
          description: 'Excellent wear resistance and vibration dampening properties. Good for water, steam, and some chemical applications.'
        },
        { 
          id: 'Cast Stainless Steel', 
          name: 'Cast Stainless Steel', 
          image: 'https://imgur.com/dMmGNrs.jpg',
          description: 'High strength and corrosion resistance. Suitable for water, steam, and various chemical applications.'
        }
      ]
    },
    {
      title: 'Select End Connection',
      type: 'cards',
      columns: 4,
      options: [
        { id: 'Flanged', name: 'Flanged', image: 'https://www.diflon.it/images/diflon/prodotti/flexiline/c2-61_main-frame.jpg' },
        { id: 'Threaded', name: 'Threaded', image: 'https://image.made-in-china.com/2f0j00oTQqLVhluzuI/Hot-Stainless-Steel-Street-Socket-Threaded-Connection.jpg' },
        { id: 'Welded', name: 'Welded', image: 'https://imgur.com/uZggOJV.png' },
        { id: 'Grooved', name: 'Grooved', image: 'https://www.victaulic.com/wp-content/uploads/2017/04/Style-177N-Cutaway.png' }
      ]
    },
    {
      title: 'Select Size',
      type: 'dropdown',
      options: [
        { id: '0.375', name: '3/8"' },
        { id: '0.5', name: '1/2"' },
        { id: '0.75', name: '3/4"' },
        { id: '1', name: '1"' },
        { id: '1.25', name: '1-1/4"' },
        { id: '1.5', name: '1-1/2"' },
        { id: '2', name: '2"' },
        { id: '2.5', name: '2-1/2"' },
        { id: '3', name: '3"' },
        { id: '4', name: '4"' },
        { id: '5', name: '5"' },
        { id: '6', name: '6"' },
        { id: '8', name: '8"' },
        { id: '10', name: '10"' },
        { id: '12', name: '12"' }
      ]
    },
    {
      title: 'Select Pressure Rating',
      type: 'dropdown',
      options: [
        { id: '125', name: '125# (PN16)' },
        { id: '150', name: '150# (PN20)' },
        { id: '300', name: '300# (PN50)' },
        { id: '800', name: '800# (PN130)' },
        { id: '1500', name: '1500# (PN250)' },
        { id: 'Table E', name: 'Table E' }
      ]
    },
    {
      title: 'Available Products',
      type: 'products',
      options: []
    }
  ];