-- BN-Infrastructure Database Schema
-- Run: /opt/lampp/bin/mysql -u root bn_infrastructure_db < data.sql

CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    company VARCHAR(255) DEFAULT NULL,
    business_type VARCHAR(100) DEFAULT NULL,
    full_name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (username),
    UNIQUE KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    category_id INT(11) DEFAULT NULL,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    old_price DECIMAL(15,2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    stock_status ENUM('in_stock','low_stock','out_of_stock') NOT NULL DEFAULT 'in_stock',
    stock_qty INT(11) NOT NULL DEFAULT 0,
    moq INT(11) NOT NULL DEFAULT 1,
    specs VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    features TEXT DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    discount_percentage INT(11) DEFAULT NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    warranty VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (sku),
    KEY (category_id),
    CONSTRAINT FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_images (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY (product_id),
    CONSTRAINT FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    invited_by INT(11) DEFAULT NULL,
    status ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_company (company_name),
    KEY idx_email (email),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    vat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    shipping DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL,
    delivery_method VARCHAR(50) DEFAULT NULL,
    delivery_fee DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    full_name VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    region VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (order_number),
    KEY (user_id),
    CONSTRAINT FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) DEFAULT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY (order_id),
    KEY (product_id),
    CONSTRAINT FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_tracking (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    status VARCHAR(50) NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY (order_id),
    CONSTRAINT FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    quotation_number VARCHAR(50) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    contact_name VARCHAR(255) DEFAULT NULL,
    contact_email VARCHAR(255) DEFAULT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    lpo_file VARCHAR(255) DEFAULT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    vat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','reviewed','approved','rejected','converted') NOT NULL DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (quotation_number),
    KEY (user_id),
    CONSTRAINT FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotation_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    quotation_id INT(11) NOT NULL,
    product_id INT(11) DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) DEFAULT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY (quotation_id),
    KEY (product_id),
    CONSTRAINT FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) DEFAULT NULL,
    quotation_id INT(11) DEFAULT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
    payment_reference VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY (order_id),
    KEY (quotation_id),
    CONSTRAINT FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    rating TINYINT(1) NOT NULL DEFAULT 5,
    title VARCHAR(255) DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    reviewer_name VARCHAR(255) DEFAULT NULL,
    reviewer_location VARCHAR(255) DEFAULT NULL,
    verified_purchase TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY (product_id),
    KEY (user_id),
    CONSTRAINT FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
