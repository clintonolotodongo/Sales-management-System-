<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/config.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$profits = $pdo->query("
    SELECT p.product_name, SUM(oi.quantity * (oi.rate - p.rate)) as profit
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    GROUP BY p.product_id
")->fetchAll();
?>

<div class="flex-grow-1 p-4">
    <h2>Profits per Product</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Profit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($profits as $profit): ?>
                <tr>
                    <td><?php echo $profit['product_name']; ?></td>
                    <td>UGX <?php echo number_format($profit['profit'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>