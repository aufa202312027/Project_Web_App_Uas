<?php
/**
 * Order Management - Generate Invoice
 * Generate PDF invoice untuk order
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$order_id = (int)($_GET['id'] ?? 0);

if ($order_id <= 0) {
    setFlashMessage('Invalid order ID.', 'error');
    header('Location: index.php');
    exit;
}

// Get order details
$order = getRecord("
    SELECT o.*, c.name as customer_name, c.email as customer_email, 
           c.phone as customer_phone, c.address as customer_address
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    WHERE o.id = ?
", [$order_id]);

if (!$order) {
    setFlashMessage('Order not found.', 'error');
    header('Location: index.php');
    exit;
}

// Get order items
$order_items = executeQuery("
    SELECT od.*, p.name as product_name, p.sku as product_sku, p.image as product_image
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
    ORDER BY od.id
", [$order_id]);

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}

$tax_rate = 0.1; // 10% tax
$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $tax_amount;

// Set content type for PDF if requested
$format = $_GET['format'] ?? 'html';
if ($format === 'pdf') {
    // For PDF generation, you would need a library like TCPDF or DOMPDF
    // For now, we'll show HTML that can be printed to PDF
    header('Content-Type: text/html; charset=utf-8');
}

$page_title = 'Invoice #' . $order['order_number'] . ' - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .btn { display: none !important; }
            body { background: white !important; }
            .invoice-container { box-shadow: none !important; margin: 0 !important; }
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
        }
        
        .company-info h1 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .invoice-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .invoice-body {
            padding: 2rem;
        }
        
        .invoice-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .table-invoice {
            margin-bottom: 0;
        }
        
        .table-invoice th {
            background: #f8f9fa;
            border-top: none;
            font-weight: 600;
            padding: 1rem 0.75rem;
        }
        
        .table-invoice td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .total-row.final {
            border-top: 2px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.25rem;
            font-weight: bold;
            color: #495057;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce7ff; color: #0056b3; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .invoice-footer {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            text-align: center;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print/Download Actions -->
        <div class="text-center py-3 no-print">
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Print Invoice
                </button>
                <a href="invoice.php?id=<?= $order_id ?>&format=pdf" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>Download PDF
                </a>
                <a href="view.php?id=<?= $order_id ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Order
                </a>
            </div>
        </div>

        <!-- Invoice Container -->
        <div class="invoice-container">
            <!-- Invoice Header -->
            <div class="invoice-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="company-info">
                            <h1><?= APP_NAME ?></h1>
                            <p class="mb-0">Professional E-Commerce Platform</p>
                            <p class="mb-0">
                                <i class="fas fa-envelope me-2"></i>admin@<?= strtolower(str_replace(' ', '', APP_NAME)) ?>.com<br>
                                <i class="fas fa-phone me-2"></i>+1 (555) 123-4567<br>
                                <i class="fas fa-map-marker-alt me-2"></i>123 Business St, City, State 12345
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="invoice-title">INVOICE</div>
                        <p class="mb-0 mt-2">
                            <strong>Invoice #:</strong> <?= htmlspecialchars($order['order_number']) ?><br>
                            <strong>Date:</strong> <?= formatDateTime($order['order_date']) ?><br>
                            <strong>Status:</strong> 
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Invoice Body -->
            <div class="invoice-body">
                <!-- Billing Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="mb-3">Bill To:</h5>
                        <div class="invoice-details">
                            <?php if ($order['customer_id']): ?>
                                <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                                <?php if ($order['customer_email']): ?>
                                    <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($order['customer_email']) ?><br>
                                <?php endif; ?>
                                <?php if ($order['customer_phone']): ?>
                                    <i class="fas fa-phone me-2"></i><?= htmlspecialchars($order['customer_phone']) ?><br>
                                <?php endif; ?>
                                <?php if ($order['customer_address']): ?>
                                    <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($order['customer_address']) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <em class="text-muted">Guest Customer</em><br>
                                <?php if ($order['customer_email']): ?>
                                    <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($order['customer_email']) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Ship To:</h5>
                        <div class="invoice-details">
                            <?php if (!empty($order['shipping_address'])): ?>
                                <i class="fas fa-shipping-fast me-2"></i><?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                            <?php else: ?>
                                <em class="text-muted">Same as billing address</em>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="table-responsive">
                    <table class="table table-invoice">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">SKU</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($order_items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <em class="text-muted">No items found in this order</em>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['product_image'])): ?>
                                                    <img src="../../assets/uploads/<?= htmlspecialchars($item['product_image']) ?>" 
                                                         alt="Product" class="me-3" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                <?php else: ?>
                                                    <div class="bg-light me-3 d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px; border-radius: 4px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <code><?= htmlspecialchars($item['product_sku']) ?></code>
                                        </td>
                                        <td class="text-center"><?= number_format($item['quantity']) ?></td>
                                        <td class="text-end"><?= formatCurrency($item['price']) ?></td>
                                        <td class="text-end">
                                            <strong><?= formatCurrency($item['quantity'] * $item['price']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="total-section">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <strong><?= formatCurrency($subtotal) ?></strong>
                            </div>
                            <div class="total-row">
                                <span>Tax (10%):</span>
                                <strong><?= formatCurrency($tax_amount) ?></strong>
                            </div>
                            <?php if (!empty($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                                <div class="total-row">
                                    <span>Shipping:</span>
                                    <strong><?= formatCurrency($order['shipping_cost']) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="total-row final">
                                <span>Total:</span>
                                <strong><?= formatCurrency($order['total_amount']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Notes -->
                <?php if (!empty($order['notes'])): ?>
                    <div class="mt-4">
                        <h6>Order Notes:</h6>
                        <div class="invoice-details">
                            <?= nl2br(htmlspecialchars($order['notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payment Information -->
                <div class="mt-4">
                    <h6>Payment Information:</h6>
                    <div class="invoice-details">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Payment Method:</strong> 
                                <?= ucfirst($order['payment_method'] ?? 'Not specified') ?><br>
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($order['payment_reference'])): ?>
                                    <strong>Payment Reference:</strong> 
                                    <?= htmlspecialchars($order['payment_reference']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($order['payment_date'])): ?>
                                    <strong>Payment Date:</strong> 
                                    <?= formatDateTime($order['payment_date']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Footer -->
            <div class="invoice-footer">
                <p class="mb-1">
                    <strong>Thank you for your business!</strong>
                </p>
                <p class="mb-0">
                    This invoice was generated on <?= date('F j, Y \a\t g:i A') ?> | 
                    For any questions, please contact us at admin@<?= strtolower(str_replace(' ', '', APP_NAME)) ?>.com
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
