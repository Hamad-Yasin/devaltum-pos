<?php

class StoreapiController extends Controller {

    public function __construct() {
        // Set CORS headers for all storefront requests
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization");
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    public function migrate() {
        $db = Database::getInstance();
        if (!$db->hasConnection()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database connection unavailable']);
            exit;
        }

        $pdo = $db->getConnection();
        $logs = [];

        try {
            // Check products table
            $productsCols = $pdo->query("DESCRIBE `products`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('is_visible_online', $productsCols)) {
                $pdo->exec("ALTER TABLE `products` ADD COLUMN `is_visible_online` TINYINT(1) NOT NULL DEFAULT 1");
                $logs[] = "Added column 'is_visible_online' to products table.";
            }
            if (!in_array('reserved_stock', $productsCols)) {
                $pdo->exec("ALTER TABLE `products` ADD COLUMN `reserved_stock` INT DEFAULT 0");
                $logs[] = "Added column 'reserved_stock' to products table.";
            }

            // Check invoices table
            $invoicesCols = $pdo->query("DESCRIBE `invoices`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('order_source', $invoicesCols)) {
                $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `order_source` VARCHAR(50) NOT NULL DEFAULT 'pos'");
                $logs[] = "Added column 'order_source' to invoices table.";
            }
            if (!in_array('online_order_status', $invoicesCols)) {
                $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `online_order_status` VARCHAR(50) NULL");
                $logs[] = "Added column 'online_order_status' to invoices table.";
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Migrations ran successfully.',
                'logs' => empty($logs) ? ['Database is already up to date.'] : $logs
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function products() {
        $db = Database::getInstance();
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        
        $sql = "SELECT id, name, model_number, barcode, unit_value, unit_type, image_path, price, (stock - reserved_stock) as available_stock 
                FROM products 
                WHERE is_visible_online = 1 AND (stock - reserved_stock) > 0 
                ORDER BY name ASC";
        
        $products = $db->fetchAll($sql);
        $formatted = [];
        
        foreach ($products as $row) {
            $image = null;
            if ($row['image_path'] && trim($row['image_path']) !== '') {
                $image = $baseUrl . '/public/' . ltrim($row['image_path'], '/');
            }
            
            $formatted[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'model_number' => $row['model_number'],
                'barcode' => $row['barcode'],
                'unit_value' => (float) $row['unit_value'],
                'unit_type' => $row['unit_type'],
                'image' => $image,
                'price' => (float) $row['price'],
                'stock' => (int) $row['available_stock']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($formatted);
        exit;
    }

    public function checkout() {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Read JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            exit;
        }

        $customerName = trim($input['customer_name'] ?? '');
        $customerPhone = trim($input['customer_phone'] ?? '');
        $deliveryMethod = trim($input['delivery_method'] ?? 'shipping');
        $shippingAddress = trim($input['shipping_address'] ?? '');
        $cartItems = $input['items'] ?? [];

        if (empty($customerName) || empty($customerPhone) || empty($cartItems)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Customer name, phone, and cart items are required.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Get or Create Customer
            $customer = $db->fetch("SELECT id FROM customers WHERE phone = ? LIMIT 1", [$customerPhone]);
            if ($customer) {
                $customerId = (int) $customer['id'];
            } else {
                $db->query("INSERT INTO customers (name, phone, address, total_spending) VALUES (?, ?, ?, 0.00)", [
                    $customerName,
                    $customerPhone,
                    $deliveryMethod === 'shipping' ? $shippingAddress : 'Self Pickup'
                ]);
                $customerId = (int) $db->lastInsertId();
            }

            // 2. Validate stock and reserve stock inside a strict FOR UPDATE lock block
            $subtotal = 0.0;
            $itemsToProcess = [];

            foreach ($cartItems as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($productId <= 0 || $quantity <= 0) {
                    throw new Exception("Invalid product ID or quantity in cart.");
                }

                // Lock the product row for update
                $stmt = $pdo->prepare("SELECT id, name, price, stock, reserved_stock FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Product ID $productId not found.");
                }

                $availableStock = (int) $product['stock'] - (int) $product['reserved_stock'];
                if ($availableStock < $quantity) {
                    throw new Exception("Insufficient stock for product '" . $product['name'] . "'. Only $availableStock items left.");
                }

                $itemTotal = (float) $product['price'] * $quantity;
                $subtotal += $itemTotal;

                $itemsToProcess[] = [
                    'id' => $productId,
                    'name' => $product['name'],
                    'price' => (float) $product['price'],
                    'quantity' => $quantity,
                    'total' => $itemTotal
                ];
            }

            // 3. Update reserved stock
            $stmtUpdate = $pdo->prepare("UPDATE products SET reserved_stock = reserved_stock + ? WHERE id = ?");
            foreach ($itemsToProcess as $procItem) {
                $stmtUpdate->execute([$procItem['quantity'], $procItem['id']]);
            }

            // 4. Create invoice (order) record
            // Generates a unique short uppercase alphanumeric code for invoice
            $customId = 'WEB-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $taxAmount = 0.00;
            $taxRate = 0.00;
            $total = $subtotal;

            $db->query("
                INSERT INTO invoices (
                    store_id, custom_id, customer_id, subtotal, discount, tax_amount, tax_rate, total, 
                    status, transaction_type, customer_name_snapshot, customer_phone_snapshot, customer_address_snapshot,
                    payment_method, sale_channel, order_source, online_order_status
                ) VALUES (?, ?, ?, ?, 0.00, ?, ?, ?, 'draft', 'Sale', ?, ?, ?, 'cod', 'online', 'web_store', 'pending_acceptance')
            ", [
                Tenant::currentStoreId() ?: 1, // Default store 1 if tenant not loaded (public checkout)
                $customId,
                $customerId,
                $subtotal,
                $taxAmount,
                $taxRate,
                $total,
                $customerName,
                $customerPhone,
                $deliveryMethod === 'shipping' ? $shippingAddress : 'Self Pickup'
            ]);

            $invoiceId = (int) $db->lastInsertId();

            // 5. Create invoice items
            $stmtItem = $pdo->prepare("
                INSERT INTO invoice_items (store_id, invoice_id, product_id, product_name, quantity, price, discount, total)
                VALUES (?, ?, ?, ?, ?, ?, 0.00, ?)
            ");
            foreach ($itemsToProcess as $procItem) {
                $stmtItem->execute([
                    Tenant::currentStoreId() ?: 1,
                    $invoiceId,
                    $procItem['id'],
                    $procItem['name'],
                    $procItem['quantity'],
                    $procItem['price'],
                    $procItem['total']
                ]);
            }

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'order_id' => $customId,
                'total' => $total,
                'message' => 'Order placed successfully!'
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function live_orders() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close(); // Release PHP session lock immediately
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $lastCheckedId = (int) ($_GET['last_id'] ?? 0);
        $db = Database::getInstance();

        // Single instant check - never hold the worker or sleep on local desktop server
        $orders = $db->fetchAll("
            SELECT id, custom_id, total, created_at 
            FROM invoices 
            WHERE order_source = 'web_store' AND online_order_status = 'pending_acceptance' AND id > ? 
            ORDER BY id ASC
        ", [$lastCheckedId]);

        if (!empty($orders)) {
            echo "data: " . json_encode($orders) . "\n\n";
        } else {
            echo ": heartbeat\n\n";
        }
        flush();
        exit;
    }

    public function active_pending_count() {
        $db = Database::getInstance();
        $orders = $db->fetchAll("
            SELECT id, custom_id, total, created_at 
            FROM invoices 
            WHERE order_source = 'web_store' AND online_order_status = 'pending_acceptance' 
            ORDER BY id ASC
        ");
        
        header('Content-Type: application/json');
        echo json_encode(['orders' => $orders]);
        exit;
    }

    private $secret_key = 'pos_storefront_secret_token_key_2026';

    private function getBearerToken() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function verifyAdminToken() {
        $token = $this->getBearerToken();
        if (!$token) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Token missing']);
            exit;
        }

        $decoded = base64_decode($token);
        if (!$decoded) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid token encoding']);
            exit;
        }

        $parts = explode('.', $decoded);
        if (count($parts) !== 3) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Malformed token']);
            exit;
        }

        list($userId, $expires, $hash) = $parts;
        if (time() > (int)$expires) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Token expired']);
            exit;
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT password, role FROM users WHERE id = ? AND status = 'active' LIMIT 1", [$userId]);
        if (!$user) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: User inactive or not found']);
            exit;
        }

        $expectedHash = md5($userId . '.' . $user['password'] . '.' . $this->secret_key . '.' . $expires);
        if ($hash !== $expectedHash) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Signature mismatch']);
            exit;
        }

        if (!in_array($user['role'], ['store_owner', 'admin', 'super_admin'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Insufficient privileges']);
            exit;
        }

        return (int) $userId;
    }

    public function settings() {
        $configPath = (defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../..') . '/storage/customer_store_settings.json';
        
        $dir = dirname($configPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyAdminToken();

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON settings payload']);
                exit;
            }

            file_put_contents($configPath, json_encode($input, JSON_PRETTY_PRINT));
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
            exit;
        }

        if (file_exists($configPath)) {
            $settings = json_decode(file_get_contents($configPath), true);
        } else {
            $settings = [
                'store_name' => 'Online Store',
                'marquee_text' => '⚡ FLASH SALE: Get flat 15% discount on all Grocery Staples! * 🚚 FREE home delivery on orders above Rs. 1500! * 🕒 Instant packing & fast Cash on Delivery (COD) confirmed within 5 minutes!',
                'shipping_fee' => 150.0,
                'free_shipping_threshold' => 1500.0,
                'discount_percentage' => 15.0,
                'contact_phone' => '+92 300 1234567',
                'contact_address' => 'Main Street, Lahore, Pakistan',
                'hero_slides' => [
                    [
                        'title' => 'Atta, Daal & Rice Staples',
                        'subtitle' => 'Get high-quality handpicked pulses, premium basmati rice, and pure wheat flour delivered straight to your home.',
                        'link' => '#section-staples',
                        'bg_gradient' => 'linear-gradient(135deg, #071935, #113264)'
                    ],
                    [
                        'title' => 'Premium Cooking Oils & Ghee',
                        'subtitle' => 'Pure cholesterol-free banaspati ghee, mustard oil, and canola oils for healthy and delicious everyday meals.',
                        'link' => '#section-oils',
                        'bg_gradient' => 'linear-gradient(135deg, #2e1a05, #50310a)'
                    ],
                    [
                        'title' => 'Hygiene & Personal Care',
                        'subtitle' => 'Keep your family safe with top-tier antiseptic soaps, anti-dandruff shampoos, detergents, and household cleaners.',
                        'link' => '#section-hygiene',
                        'bg_gradient' => 'linear-gradient(135deg, #1f103b, #3d2073)'
                    ]
                ]
            ];
            file_put_contents($configPath, json_encode($settings, JSON_PRETTY_PRINT));
        }

        header('Content-Type: application/json');
        echo json_encode($settings);
        exit;
    }

    public function admin_login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $usernameOrEmail = trim($input['username_or_email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Username/email and password are required.']);
            exit;
        }

        $db = Database::getInstance();
        $user = $db->fetch("
            SELECT id, name, username, email, password, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
            LIMIT 1
        ", [$usernameOrEmail, $usernameOrEmail]);

        if ($user && password_verify($password, $user['password'])) {
            if (in_array($user['role'], ['store_owner', 'admin', 'super_admin'])) {
                $token = $this->generateToken($user['id'], $user['password']);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => (int) $user['id'],
                        'name' => $user['name'],
                        'role' => $user['role']
                    ]
                ]);
                exit;
            } else {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden: Insufficient privileges.']);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials.']);
            exit;
        }
    }

    private function generateToken($userId, $passwordHash) {
        $expires = time() + (24 * 3600); // 24 hours
        $hash = md5($userId . '.' . $passwordHash . '.' . $this->secret_key . '.' . $expires);
        return base64_encode($userId . '.' . $expires . '.' . $hash);
    }

    public function admin_orders() {
        $this->verifyAdminToken();
        $db = Database::getInstance();
        
        $orders = $db->fetchAll("
            SELECT id, custom_id, customer_name_snapshot, customer_phone_snapshot, customer_address_snapshot, 
                   subtotal, discount, tax_amount, total, status, online_order_status, created_at
            FROM invoices 
            WHERE order_source = 'web_store' 
            ORDER BY id DESC
        ");

        header('Content-Type: application/json');
        echo json_encode($orders);
        exit;
    }

    public function admin_order_items($invoiceId = null) {
        $this->verifyAdminToken();
        $invoiceId = (int) $invoiceId;
        
        if ($invoiceId <= 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing invoice ID.']);
            exit;
        }

        $db = Database::getInstance();
        $items = $db->fetchAll("
            SELECT id, product_name, quantity, price, total 
            FROM invoice_items 
            WHERE invoice_id = ?
        ", [$invoiceId]);

        header('Content-Type: application/json');
        echo json_encode($items);
        exit;
    }

    public function admin_update_order_status() {
        $this->verifyAdminToken();
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = (int) ($input['invoice_id'] ?? 0);
        $newStatus = trim($input['status'] ?? '');

        if ($invoiceId <= 0 || !in_array($newStatus, ['accepted', 'cancelled', 'delivered'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid invoice ID or status value.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, status, online_order_status FROM invoices WHERE id = ? FOR UPDATE");
            $stmt->execute([$invoiceId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception("Order not found.");
            }

            $currentOnlineStatus = $order['online_order_status'];

            if ($newStatus === 'accepted') {
                if ($currentOnlineStatus !== 'pending_acceptance') {
                    throw new Exception("Order is already in '$currentOnlineStatus' status and cannot be accepted.");
                }

                $items = $db->fetchAll("SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?", [$invoiceId]);
                
                $stmtProductUpdate = $pdo->prepare("UPDATE products SET stock = stock - ?, reserved_stock = reserved_stock - ? WHERE id = ?");
                foreach ($items as $item) {
                    $stmtProductUpdate->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                }

                $db->query("UPDATE invoices SET status = 'paid', online_order_status = 'accepted' WHERE id = ?", [$invoiceId]);

            } else if ($newStatus === 'cancelled') {
                if ($currentOnlineStatus === 'pending_acceptance') {
                    $items = $db->fetchAll("SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?", [$invoiceId]);
                    $stmtProductUpdate = $pdo->prepare("UPDATE products SET reserved_stock = reserved_stock - ? WHERE id = ?");
                    foreach ($items as $item) {
                        $stmtProductUpdate->execute([$item['quantity'], $item['product_id']]);
                    }
                } else if ($currentOnlineStatus === 'accepted' || $currentOnlineStatus === 'delivered') {
                    $items = $db->fetchAll("SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?", [$invoiceId]);
                    $stmtProductUpdate = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    foreach ($items as $item) {
                        $stmtProductUpdate->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                $db->query("UPDATE invoices SET status = 'cancelled', online_order_status = 'cancelled' WHERE id = ?", [$invoiceId]);

            } else if ($newStatus === 'delivered') {
                if ($currentOnlineStatus !== 'accepted') {
                    throw new Exception("Only accepted orders can be marked as delivered.");
                }
                
                $db->query("UPDATE invoices SET online_order_status = 'delivered' WHERE id = ?", [$invoiceId]);
            }

            $pdo->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Order status updated to $newStatus successfully."]);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function admin_products() {
        $this->verifyAdminToken();
        $db = Database::getInstance();
        
        $products = $db->fetchAll("
            SELECT id, name, model_number, barcode, price, stock, is_visible_online 
            FROM products 
            ORDER BY name ASC
        ");

        $formatted = [];
        foreach ($products as $row) {
            $formatted[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'model_number' => $row['model_number'],
                'barcode' => $row['barcode'],
                'price' => (float) $row['price'],
                'stock' => (int) $row['stock'],
                'is_visible_online' => (int) $row['is_visible_online']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($formatted);
        exit;
    }

    public function admin_toggle_product() {
        $this->verifyAdminToken();
        $db = Database::getInstance();

        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int) ($input['product_id'] ?? 0);
        $isVisible = (int) ($input['is_visible_online'] ?? 0);

        if ($productId <= 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product ID.']);
            exit;
        }

        $db->query("UPDATE products SET is_visible_online = ? WHERE id = ?", [$isVisible, $productId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Product online visibility updated.']);
        exit;
    }
}
