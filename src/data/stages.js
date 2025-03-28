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
        { 
          id: 'stainless-steel', 
          name: 'Stainless Steel', 
          image: 'https://media.istockphoto.com/id/484048852/photo/metal-cube.jpg?s=612x612&w=0&k=20&c=aOQa80TqZfKgJR1_CgfsT8XL-0TUvnfWhoMrzxrzk2I=',
          description: 'Corrosion resistant and suitable for food, pharmaceutical, and chemical applications. Ideal for aggressive media.'
        },
        { 
          id: 'carbon-steel', 
          name: 'Carbon Steel', 
          image: 'https://cdn11.bigcommerce.com/s-zgzol/images/stencil/1280x1280/products/51883/258495/united-scientific-steel-cube-1__34316.1728986818.jpg?c=2',
          description: 'Cost-effective and strong with good thermal conductivity. Best for non-corrosive applications and high-temperature service.'
        },
        { 
          id: 'cast-iron', 
          name: 'Cast Iron', 
          image: 'https://live.staticflickr.com/4058/4604173675_7e6d2eb027_b.jpg',
          description: 'Excellent wear resistance and vibration dampening properties. Good for water, steam, and some chemical applications.'
        }
      ]
    },
    {
      title: 'Select End Connection',
      type: 'cards',
      columns: 4,
      options: [
        { id: 'flanged', name: 'Flanged', image: 'https://www.diflon.it/images/diflon/prodotti/flexiline/c2-61_main-frame.jpg' },
        { id: 'threaded', name: 'Threaded', image: 'https://image.made-in-china.com/2f0j00oTQqLVhluzuI/Hot-Stainless-Steel-Street-Socket-Threaded-Connection.jpg' },
        { id: 'welded', name: 'Welded', image: 'src\\assets\\weld-socket.png' },
        { id: 'grooved', name: 'Grooved', image: 'https://www.victaulic.com/wp-content/uploads/2017/04/Style-177N-Cutaway.png' }
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