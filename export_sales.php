<?php
require_once 'includes/config.php';

// Fetch detailed sales data for export
$sales = $pdo->query("
    SELECT o.order_id, o.order_date, o.grand_total, oi.quantity, oi.rate, p.product_name
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    ORDER BY o.order_date DESC
")->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sales_report.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Order ID', 'Order Date', 'Product Name', 'Quantity', 'Price (UGX)', 'Total (UGX)']);

// Write sales data to CSV
foreach ($sales as $sale) {
    fputcsv($output, [
        $sale['order_id'],
        $sale['order_date'],
        $sale['product_name'],
        $sale['quantity'],
        number_format($sale['rate'], 2),
        number_format($sale['grand_total'], 2)
    ]);
}

fclose($output);
exit();
?>