<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/config.php';
include '../includes/header.php';
include '../includes/sidebar.php';

// Fetch detailed sales data including product, price, and quantity
$sales = $pdo->query("
    SELECT o.order_id, o.order_date, o.grand_total, oi.quantity, oi.rate, p.product_name
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    ORDER BY o.order_date DESC
")->fetchAll();

// Prepare data for the chart
$labels = [];
$data = [];
$tooltipData = []; // Store additional data for tooltips (product, quantity, rate)
$orderTotals = []; // Track total per order_date for the chart

foreach ($sales as $sale) {
    $date = $sale['order_date'];
    if (!in_array($date, $labels)) {
        $labels[] = $date;
        $orderTotals[$date] = 0;
    }
    $orderTotals[$date] += $sale['grand_total'];

    // Store tooltip data for each sale
    $tooltipData[] = [
        'order_id' => $sale['order_id'],
        'date' => $sale['order_date'],
        'product' => $sale['product_name'],
        'quantity' => $sale['quantity'],
        'rate' => $sale['rate'],
        'total' => $sale['grand_total']
    ];
}

// Prepare chart data (total sales per date)
foreach ($labels as $date) {
    $data[] = $orderTotals[$date];
}
?>

<div class="flex-grow-1 p-4">
    <h2>Sales Reports</h2>
    <canvas id="salesChart" width="400" height="200"></canvas>
    <a href="../export_sales.php" class="btn btn-primary mt-3">Export to CSV</a>
</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const tooltipData = <?php echo json_encode($tooltipData); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Sales per Date',
            data: <?php echo json_encode($data); ?>,
            borderColor: 'rgba(75, 192, 192, 1)',
            fill: false
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Sales (UGX)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Order Date'
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                        const index = context.dataIndex;
                        const date = context.label;
                        // Filter tooltipData for the current date
                        const items = tooltipData.filter(item => item.date === date);
                        let tooltipText = [];
                        items.forEach(item => {
                            tooltipText.push(
                                `Order ID: ${item.order_id}`,
                                `Product: ${item.product}`,
                                `Quantity: ${item.quantity}`,
                                `Price: UGX${item.rate.toFixed(2)}`,
                                `Total: UGX${item.total.toFixed(2)}`,
                                '-------------------'
                            );
                        });
                        return tooltipText;
                    }
                }
            }
        }
    }
});
</script>


</body>
</html>