# Hi5ve MarketPlace

A comprehensive grocery marketplace built with PHP, featuring WhatsApp integration for seamless customer communication and ordering. Designed specifically for the Nigerian market with support for Nigerian Naira (₦) currency.

## 🌟 Features

### Core Functionality
- **Multi-User System**: Admin, Registered Customers, and Guest users
- **Product Management**: Categories, inventory tracking, discounts, featured products
- **Shopping Cart**: Dual cart system (registered users + guest sessions)
- **Order Management**: Complete order lifecycle with status tracking
- **WhatsApp Integration**: Order confirmations, status updates, direct ordering
- **Payment Methods**: Online payment and Cash on Delivery
- **Responsive Design**: Mobile-first approach with Tailwind CSS

### User Features
- **Guest Shopping**: Browse and purchase without registration
- **User Registration/Login**: Secure account management
- **Profile Management**: Update personal information and passwords
- **Order History**: View past orders with filtering and pagination
- **Search & Filter**: Product search with category filtering
- **Real-time Cart**: AJAX-powered cart updates

### Admin Features
- **Dashboard**: Overview of orders, products, and customers
- **Product Management**: CRUD operations with image uploads
- **Category Management**: Organize products efficiently
- **Order Management**: Process and track customer orders
- **Customer Management**: View and manage user accounts
- **WhatsApp Notifications**: Automated customer communications

### WhatsApp Integration
- **Order Confirmations**: Automatic order details via WhatsApp
- **Status Updates**: Real-time order status notifications
- **Direct Ordering**: Customers can order directly via WhatsApp
- **Customer Support**: Floating WhatsApp button for instant help
- **Custom Messages**: Contextual WhatsApp links throughout the site

## 🚀 Quick Start

### Prerequisites
- **XAMPP** (or similar PHP development environment)
- **PHP 7.4+** with PDO extension
- **MySQL 5.7+**
- **Web browser** with JavaScript enabled

### Installation

1. **Download/Clone the project**
   ```bash
   # Place in your XAMPP htdocs directory
   cd /Applications/XAMPP/xamppfiles/htdocs/
   # Extract or clone the project as 'mart3'
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE mart3;
   ```

3. **Configure Database Connection**
   - Open `config/database.php`
   - Update credentials if needed (default: host=localhost, user=root, password='')

4. **Run Setup**
   - Navigate to `http://localhost/mart3/setup.php`
   - Enter setup password: `hi5ve_setup_2024`
   - Click "Run Database Setup"
   - Delete `setup.php` after successful setup

5. **Access the Application**
   - **Homepage**: `http://localhost/mart3/`
   - **Admin Login**: `http://localhost/mart3/login.php`
     - Email: `admin@hi5ve.com`
     - Password: `password`

### Configuration

#### WhatsApp Settings
Update `config/config.php`:
```php
define('WHATSAPP_NUMBER', '+2348123456789'); // Your business WhatsApp number
```

#### Site Settings
```php
define('SITE_NAME', 'Hi5ve MarketPlace');
define('SITE_URL', 'http://localhost/mart3');
define('CURRENCY', '₦');
```

## 📁 Project Structure

```
mart3/
├── config/
│   ├── config.php          # Site configuration and utilities
│   └── database.php        # Database connection class
├── classes/
│   ├── User.php           # User management and authentication
│   ├── Product.php        # Product CRUD and search
│   ├── Category.php       # Category management
│   ├── Cart.php           # Shopping cart functionality
│   └── Order.php          # Order processing and WhatsApp integration
├── includes/
│   ├── header.php         # Site header with navigation
│   └── footer.php         # Site footer
├── ajax/
│   └── cart.php           # AJAX cart operations
├── database/
│   └── schema.sql         # Database schema and sample data
├── uploads/
│   ├── products/          # Product images
│   └── categories/        # Category images
├── assets/
│   └── images/            # Static assets
├── admin/                 # Admin panel (to be implemented)
├── index.php              # Homepage
├── products.php           # Product listing with search/filter
├── cart.php               # Shopping cart page
├── login.php              # User login
├── register.php           # User registration
├── logout.php             # User logout
├── profile.php            # User profile management
├── orders.php             # Order history
├── setup.php              # Database setup script
└── README.md              # This file
```

## 🛠 Key Components

### Database Schema
- **users**: Customer and admin accounts
- **categories**: Product categories
- **products**: Product catalog with inventory
- **cart**: Registered user shopping carts
- **guest_cart**: Session-based guest carts
- **orders**: Order records
- **order_items**: Order line items
- **whatsapp_messages**: WhatsApp communication log
- **site_settings**: Application configuration

### Classes Overview

#### User Class (`classes/User.php`)
- User registration and authentication
- Profile management
- Password changes
- Admin user management

#### Product Class (`classes/Product.php`)
- Product CRUD operations
- Search and filtering
- Category-based retrieval
- Stock management
- Featured products

#### Cart Class (`classes/Cart.php`)
- Dual cart system (user/guest)
- Real-time stock validation
- Delivery fee calculation
- Cart transfer on login/registration

#### Order Class (`classes/Order.php`)
- Order creation and management
- WhatsApp integration
- Status tracking
- Inventory updates

### AJAX Functionality
The `ajax/cart.php` handles:
- Add to cart
- Update quantities
- Remove items
- Cart summary
- WhatsApp checkout
- Stock validation

## 🎨 Frontend Features

### Responsive Design
- **Mobile-first**: Optimized for mobile devices
- **Tailwind CSS**: Modern utility-first CSS framework
- **Font Awesome**: Comprehensive icon library
- **Interactive Elements**: Hover effects, transitions, animations

### User Experience
- **Real-time Updates**: Cart count, stock status
- **Search & Filter**: Product discovery
- **Pagination**: Efficient data browsing
- **Notifications**: Success/error messages
- **WhatsApp Integration**: Seamless communication

## 📱 WhatsApp Features

### Automated Messages
- **Order Confirmation**: Detailed order summary
- **Status Updates**: Order progress notifications
- **Stock Alerts**: Low inventory notifications

### Direct Ordering
- **Product Links**: Direct WhatsApp ordering from product pages
- **Cart Checkout**: Complete cart ordering via WhatsApp
- **Support**: Instant customer support access

### Message Templates
All WhatsApp messages are contextual and include:
- Order details and totals
- Customer information
- Product specifications
- Payment and delivery information

## 🔧 Customization

### Adding New Features
1. Create new PHP classes in `classes/` directory
2. Add database tables via migration scripts
3. Update navigation in `includes/header.php`
4. Add new pages following existing patterns

### Styling Changes
- Modify Tailwind classes in templates
- Add custom CSS in `includes/header.php`
- Update color scheme in configuration

### WhatsApp Customization
- Update message templates in `classes/Order.php`
- Modify WhatsApp number in `config/config.php`
- Customize floating button in `includes/header.php`

## 🚨 Security Features

- **PDO Prepared Statements**: SQL injection prevention
- **Password Hashing**: Secure password storage
- **Input Sanitization**: XSS protection
- **Session Management**: Secure user sessions
- **Setup Protection**: Password-protected database setup

## 📊 Admin Features (Coming Soon)

The admin panel will include:
- Dashboard with analytics
- Product management interface
- Order processing tools
- Customer management
- WhatsApp message logs
- Site configuration

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Support

For support and questions:
- **WhatsApp**: Contact via the integrated WhatsApp features
- **Email**: Create an issue in the repository
- **Documentation**: Refer to code comments and this README

## 🔄 Version History

### v1.0.0 (Current)
- Initial release
- Core marketplace functionality
- WhatsApp integration
- User management
- Product catalog
- Shopping cart
- Order processing
- Responsive design

---

**Built with ❤️ for the Nigerian market**

*Hi5ve MarketPlace - Your trusted grocery delivery partner* 