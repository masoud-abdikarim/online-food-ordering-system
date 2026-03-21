<?php
/**
 * Full order details for Admin (items, customer, address).
 * GET: order_id
 */
require_once __DIR__ . '/session_auth.php';
require_authenticated_session(['Admin'], 'auto');

header('Content-Type: text/html; charset=utf-8');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo '<p class="text-danger">Invalid order</p>';
    exit;
}

$sql = "SELECT o.*, u.name AS customer_name, u.phone AS customer_phone,
        a.address AS delivery_address, a.city AS addr_city, a.postal_code,
        d.delivery_id, d.status AS delivery_status, d.assigned_at, d.delivered_at,
        dp.name AS delivery_person_name
        FROM orders o
        JOIN user u ON o.user_id = u.user_id
        LEFT JOIN address a ON a.order_id = o.order_id
        LEFT JOIN delivery d ON d.order_id = o.order_id
        LEFT JOIN user dp ON d.delivery_person_id = dp.user_id
        WHERE o.order_id = $order_id
        LIMIT 1";
$res = mysqli_query($connection, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    echo '<p class="text-danger">Order not found.</p>';
    exit;
}
$order = mysqli_fetch_assoc($res);

$items_sql = "SELECT oi.quantity, oi.price, m.name AS item_name, m.item_id
              FROM orderitem oi
              JOIN menuitem m ON oi.menu_item_id = m.item_id
              WHERE oi.order_id = $order_id";
$items_res = mysqli_query($connection, $items_sql);
?>
<div class="kaah-admin-order-detail">
    <div class="detail-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:18px;">
        <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;">
            <strong style="display:block;margin-bottom:8px;color:#64748b;font-size:0.75rem;text-transform:uppercase;">Order</strong>
            <div>#<?php echo (int)$order['order_id']; ?></div>
            <div style="margin-top:6px;font-size:0.9rem;"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
        </div>
        <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;">
            <strong style="display:block;margin-bottom:8px;color:#64748b;font-size:0.75rem;text-transform:uppercase;">Customer</strong>
            <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
            <div style="font-size:0.9rem;"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
        </div>
        <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;">
            <strong style="display:block;margin-bottom:8px;color:#64748b;font-size:0.75rem;text-transform:uppercase;">Amount</strong>
            <div style="font-size:1.25rem;font-weight:800;">$<?php echo number_format((float)$order['total_amount'], 2); ?></div>
            <div style="font-size:0.85rem;margin-top:4px;">Payment: <?php echo htmlspecialchars($order['payment_status']); ?></div>
        </div>
    </div>

    <?php if (!empty($order['delivery_address'])): ?>
    <div style="margin-bottom:16px;padding:14px;background:#fff7ed;border-radius:12px;border:1px solid #fed7aa;">
        <strong><i class="fas fa-location-dot"></i> Delivery address</strong>
        <p style="margin:8px 0 0;line-height:1.5;"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
        <?php if (!empty($order['addr_city']) || !empty($order['postal_code'])): ?>
            <br><span class="text-muted"><?php echo htmlspecialchars(trim(($order['addr_city'] ?? '') . ' ' . ($order['postal_code'] ?? ''))); ?></span>
        <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <p class="text-muted" style="margin-bottom:16px;"><i class="fas fa-info-circle"></i> No address on file for this order.</p>
    <?php endif; ?>

    <div style="margin-bottom:16px;">
        <strong>Order status:</strong>
        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>" style="margin-left:8px;">
            <?php echo htmlspecialchars($order['status']); ?>
        </span>
        <?php if (!empty($order['delivery_status'])): ?>
            <span style="margin-left:12px;"><strong>Delivery:</strong>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['delivery_status'])); ?>">
                    <?php echo htmlspecialchars($order['delivery_status']); ?>
                </span>
            </span>
        <?php endif; ?>
        <?php if (!empty($order['delivery_person_name'])): ?>
            <div style="margin-top:8px;font-size:0.9rem;">Driver: <strong><?php echo htmlspecialchars($order['delivery_person_name']); ?></strong></div>
        <?php endif; ?>
    </div>

    <h4 style="margin:16px 0 10px;font-size:0.95rem;">Line items</h4>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="padding:10px;text-align:left;">Item</th>
                    <th style="padding:10px;">Qty</th>
                    <th style="padding:10px;">Price</th>
                    <th style="padding:10px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($items_res && mysqli_num_rows($items_res) > 0):
                mysqli_data_seek($items_res, 0);
                while ($row = mysqli_fetch_assoc($items_res)):
                    $sub = (float)$row['price'] * (int)$row['quantity'];
            ?>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:10px;"><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td style="padding:10px;text-align:center;"><?php echo (int)$row['quantity']; ?></td>
                    <td style="padding:10px;text-align:right;">$<?php echo number_format((float)$row['price'], 2); ?></td>
                    <td style="padding:10px;text-align:right;">$<?php echo number_format($sub, 2); ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr><td colspan="4" style="padding:16px;">No line items.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
