<?php
require_once '../includes/config.php';
require_once '../functions/order.php';

if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $items = getOrderItems($pdo, $order_id);
    foreach ($items as $item) {
        echo "<tr>
                <td>{$item['product_name']}</td>
                <td>{$item['quantity']}</td>
                <td>\${$item['rate']}</td>
                <td>\${$item['total']}</td>
              </tr>";
    }
}
?>