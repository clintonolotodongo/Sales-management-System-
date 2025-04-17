<?php
function createOrder($pdo, $order_date, $client_name, $client_contact, $sub_total, $vat, $total_amount, $discount, $grand_total, $paid, $due, $payment_type, $payment_status, $payment_place, $gstn, $order_status, $user_id) {
    $stmt = $pdo->prepare("INSERT INTO orders (order_date, client_name, client_contact, sub_total, vat, total_amount, discount, grand_total, paid, due, payment_type, payment_status, payment_place, gstn, order_status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$order_date, $client_name, $client_contact, $sub_total, $vat, $total_amount, $discount, $grand_total, $paid, $due, $payment_type, $payment_status, $payment_place, $gstn, $order_status, $user_id]);
    return $pdo->lastInsertId();
}

function addOrderItem($pdo, $order_id, $product_id, $quantity, $rate, $total, $order_item_status) {
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, rate, total, order_item_status) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$order_id, $product_id, $quantity, $rate, $total, $order_item_status]);
}

function getOrders($pdo) {
    $stmt = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.user_id");
    return $stmt->fetchAll();
}

function getOrderItems($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT oi.*, p.product_name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

function deleteOrder($pdo, $order_id) {
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
    $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
    return $stmt->execute([$order_id]);
}
?>