# 🔧 Technical Documentation - Web Application

## 📋 System Architecture

### Technology Stack
- **Backend:** PHP 7.4+ (Native/Vanilla PHP)
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **UI Framework:** Bootstrap 4/5
- **JavaScript Library:** jQuery 3.6+
- **Server:** Apache, Nginx, or PHP Built-in Server

### Architecture Pattern
- **MVC-Like Structure:** Separation of concerns
- **Session-Based Authentication:** PHP native sessions
- **Database Abstraction:** PDO with prepared statements
- **RESTful-like Endpoints:** Clean URL structure

## 📁 Directory Structure

```
project_web_app/
├── index.php                 # Entry point / Landing page
├── config/                   # Configuration files
│   ├── database.php         # Database connection
│   ├── config.php           # App configuration
│   └── session.php          # Session management
├── includes/                 # Shared components
│   ├── header.php           # HTML header
│   ├── footer.php           # HTML footer
│   ├── navbar.php           # Navigation
│   └── functions.php        # Helper functions
├── auth/                     # Authentication system
│   ├── login.php            # Login form & processing
│   ├── logout.php           # Logout handler
│   └── check_session.php    # Session validation
├── admin/                    # Admin panel
│   ├── dashboard.php        # Admin dashboard
│   ├── users/               # User management
│   ├── products/            # Product management
│   └── reports/             # Reports system
├── assets/                   # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Image assets
├── database/                 # Database files
│   ├── create_database.sql  # Database structure
│   └── sample_data.sql      # Sample data
└── docs/                     # Documentation
```

## 🗄️ Database Design

### Entity Relationship Diagram (ERD)

```
[users] ←--→ [orders] ←--→ [customers]
    ↓           ↓
[activity_logs] [order_details] ←--→ [products] ←--→ [categories]
                    ↓               ↓           ↓
               [payments]      [inventory]  [suppliers]
```

### Table Relationships

**Primary Relationships:**
- `users` → `orders` (One-to-Many)
- `customers` → `orders` (One-to-Many)
- `orders` → `order_details` (One-to-Many)
- `products` → `order_details` (One-to-Many)
- `categories` → `products` (One-to-Many)
- `suppliers` → `products` (One-to-Many)

**Secondary Relationships:**
- `orders` → `payments` (One-to-Many)
- `products` → `inventory` (One-to-Many)
- `users` → `activity_logs` (One-to-Many)

### Database Constraints

**Foreign Key Constraints:**
- `ON DELETE RESTRICT`: Prevent deletion if referenced
- `ON DELETE CASCADE`: Auto-delete related records
- `ON DELETE SET NULL`: Set foreign key to NULL

**Business Rules:**
- Stock cannot be negative
- Order total must match sum of order details
- User roles limited to 'admin' or 'user'
- Order status workflow enforced

## 🔐 Security Implementation

### Authentication System

**Session-Based Authentication:**
```php
// Session start in all protected pages
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /auth/login.php');
    exit;
}
```

**Password Security:**
```php
// Password hashing (registration)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Password verification (login)
if (password_verify($password, $hashed_password)) {
    // Login successful
}
```

### SQL Injection Prevention

**Prepared Statements:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);
```

**Input Validation:**
```php
// Sanitize input
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
```

### XSS Prevention

**Output Escaping:**
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

**CSP Headers:**
```php
header("Content-Security-Policy: default-src 'self'");
```

## 🔄 Core Functions

### Database Connection (`config/database.php`)

```php
class Database {
    private $host = 'localhost';
    private $dbname = 'web_app_db';
    private $username = 'root';
    private $password = '';
    
    public function connect() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            return $pdo;
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}
```

### Session Management (`config/session.php`)

```php
// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

// Session functions
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function checkLogin() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireLogin() {
    if (!checkLogin()) {
        header('Location: /auth/login.php');
        exit;
    }
}
```

### Helper Functions (`includes/functions.php`)

```php
// Utility functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function logActivity($user_id, $action, $table, $record_id, $description) {
    // Log user activity to database
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
```

## 🎨 Frontend Architecture

### CSS Structure

```css
/* assets/css/style.css */
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
}

/* Component-based styling */
.card { /* Base card styles */ }
.btn { /* Button styles */ }
.form-control { /* Form input styles */ }
.table { /* Table styles */ }
```

### JavaScript Architecture

```javascript
// assets/js/custom.js
const App = {
    init: function() {
        this.initDataTables();
        this.initFormValidation();
        this.initAjaxRequests();
    },
    
    initDataTables: function() {
        $('.data-table').DataTable({
            responsive: true,
            language: {
                url: '/assets/js/dataTables.indonesia.json'
            }
        });
    },
    
    initFormValidation: function() {
        $('form.needs-validation').on('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(this).addClass('was-validated');
        });
    }
};

$(document).ready(function() {
    App.init();
});
```

## 🔗 API Endpoints

### User Management

```php
// admin/users/process.php
switch ($_POST['action']) {
    case 'create':
        // POST /admin/users/process.php
        // Create new user
        break;
    
    case 'update':
        // PUT /admin/users/process.php
        // Update existing user
        break;
    
    case 'delete':
        // DELETE /admin/users/process.php
        // Soft delete user
        break;
}
```

### Product Management

```php
// admin/products/process.php
switch ($_POST['action']) {
    case 'create':
        // Create product with image upload
        break;
    
    case 'update':
        // Update product details
        break;
    
    case 'update_stock':
        // Update product stock
        break;
}
```

## 📊 Performance Optimization

### Database Optimization

**Indexes:**
```sql
-- Frequently queried columns
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_orders_date ON orders(order_date);
CREATE INDEX idx_users_username ON users(username);
```

**Query Optimization:**
```php
// Use LIMIT for pagination
$stmt = $pdo->prepare("SELECT * FROM products LIMIT ? OFFSET ?");

// Use JOINs instead of multiple queries
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
");
```

### Frontend Optimization

**CSS/JS Minification:**
```html
<!-- Production -->
<link href="/assets/css/style.min.css" rel="stylesheet">
<script src="/assets/js/app.min.js"></script>
```

**Image Optimization:**
```php
// Resize uploaded images
function resizeImage($source, $destination, $max_width = 800) {
    // Image resize logic
}
```

## 🧪 Testing Strategy

### Unit Testing

```php
// tests/UserTest.php
class UserTest extends PHPUnit\Framework\TestCase {
    public function testUserCreation() {
        $user = new User();
        $result = $user->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->assertTrue($result);
    }
}
```

### Integration Testing

```php
// Test database connections
// Test authentication flow
// Test CRUD operations
```

### Manual Testing Checklist

- [ ] Login/Logout functionality
- [ ] User creation and management
- [ ] Product CRUD operations
- [ ] Order processing workflow
- [ ] Payment recording
- [ ] Inventory updates
- [ ] Report generation
- [ ] Security validations

## 🚀 Deployment Guide

### Production Checklist

**Security:**
- [ ] Change default passwords
- [ ] Enable HTTPS
- [ ] Set secure session settings
- [ ] Configure CSP headers
- [ ] Hide error messages

**Performance:**
- [ ] Enable PHP OPcache
- [ ] Configure database connection pooling
- [ ] Implement caching strategy
- [ ] Optimize images
- [ ] Minify CSS/JS

**Monitoring:**
- [ ] Setup error logging
- [ ] Configure database monitoring
- [ ] Setup backup routine
- [ ] Monitor disk space
- [ ] Track performance metrics

### Environment Configuration

```php
// config/config.php
define('ENVIRONMENT', 'production'); // development, staging, production

// Development settings
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Production settings
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
```

## 📈 Scaling Considerations

### Horizontal Scaling
- Load balancer configuration
- Session sharing (Redis/Memcached)
- Database read replicas
- CDN for static assets

### Vertical Scaling
- PHP-FPM optimization
- MySQL query optimization
- Memory management
- CPU optimization

## 🔍 Troubleshooting

### Common Issues

**Database Connection:**
```php
// Check PDO connection
try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

**Session Issues:**
```php
// Debug session
var_dump($_SESSION);
echo "Session ID: " . session_id();
echo "Session status: " . session_status();
```

**Permission Problems:**
```bash
# Check file permissions
ls -la /path/to/project
chmod -R 755 /path/to/project
chown -R www-data:www-data /path/to/project
```

---

**Technical Documentation v1.0** | Last Updated: June 2025

## 📞 Support & Maintenance

For technical support:
1. Check error logs first
2. Review this documentation
3. Test in development environment
4. Contact development team

Regular maintenance tasks:
- Database cleanup
- Log rotation
- Security updates
- Performance monitoring