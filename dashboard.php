<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/config.php';
require_once '../functions/order.php';
require_once '../functions/product.php';
include '../includes/header.php';
include '../includes/sidebar.php';

// Fetch dashboard stats
$total_sales = $pdo->query("SELECT SUM(grand_total) as total FROM orders")->fetch()['total'] ?? 0;
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_brands = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();

// Pagination settings
$limit = 5; // Number of sales per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of sales for pagination
$total_sales_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_pages = ceil($total_sales_count / $limit);

// Fetch sales with pagination
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.client_name, o.client_contact, o.sub_total, o.vat, o.total_amount, o.discount, o.grand_total, o.paid, o.due, o.payment_type, o.payment_status, o.payment_place, o.gstn, o.order_status, o.user_id
    FROM orders o
    ORDER BY o.order_date DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$sales = $stmt->fetchAll();

// Fetch products for the edit modal
$products = getProducts($pdo);

// Handle order edit submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $order_date = $_POST['order_date'];
    $client_name = $_POST['client_name'];
    $client_contact = $_POST['client_contact'];
    $sub_total = $_POST['sub_total'];
    $vat = $_POST['vat'];
    $total_amount = $_POST['total_amount'];
    $discount = $_POST['discount'];
    $grand_total = $_POST['grand_total'];
    $paid = $_POST['paid'];
    $due = $_POST['due'];
    $payment_type = $_POST['payment_type'];
    $payment_status = $_POST['payment_status'];
    $payment_place = $_POST['payment_place'];
    $gstn = $_POST['gstn'];
    $order_status = $_POST['order_status'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $original_items = $stmt->fetchAll();

    foreach ($original_items as $item) {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }

    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $rates = $_POST['rate'];
    $totals = $_POST['total'];

    $stock_check_passed = true;
    for ($i = 0; $i < count($product_ids); $i++) {
        $product_id = $product_ids[$i];
        $quantity = $quantities[$i];

        if (!checkProductStock($pdo, $product_id, $quantity)) {
            $stmt = $pdo->prepare("SELECT product_name FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product_name = $stmt->fetchColumn();
            $errors[] = "Not enough stock for product: $product_name. Requested: $quantity.";
            $stock_check_passed = false;
        }
    }

    if ($stock_check_passed && empty($errors)) {
        updateOrder($pdo, $order_id, $order_date, $client_name, $client_contact, $sub_total, $vat, $total_amount, $discount, $grand_total, $paid, $due, $payment_type, $payment_status, $payment_place, $gstn, $order_status, $user_id);

        deleteOrderItems($pdo, $order_id);

        for ($i = 0; $i < count($product_ids); $i++) {
            addOrderItem($pdo, $order_id, $product_ids[$i], $quantities[$i], $rates[$i], $totals[$i], 1);
            updateProductQuantity($pdo, $product_ids[$i], $quantities[$i]);
        }

        header("Location: dashboard.php?page=$page");
        exit();
    } else {
        foreach ($original_items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
    }
}

if (isset($_GET['delete'])) {
    $order_id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }

    deleteOrder($pdo, $order_id);
    header("Location: dashboard.php?page=$page");
    exit();
}
?>

<div class="flex-grow-1 p-4">
    <h2>Dashboard</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Total Sales</h5>
                    <h3>UGX<?php echo number_format($total_sales, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Products</h5>
                    <h3><?php echo $total_products; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Categories</h5>
                    <h3><?php echo $total_categories; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Brands</h5>
                    <h3><?php echo $total_brands; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mt-4">Sales List</h3>
    <!-- Search Bar -->
    <div class="mb-3">
        <input type="text" id="salesSearch" class="form-control" placeholder="Search by Order ID or Client Name">
    </div>
    <table class="table table-bordered" id="salesTable">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Client Name</th>
                <th>Grand Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td data-label="Order ID"><?php echo $sale['order_id']; ?></td>
                    <td data-label="Order Date"><?php echo $sale['order_date']; ?></td>
                    <td data-label="Client Name"><?php echo $sale['client_name']; ?></td>
                    <td data-label="Grand Total">UGX<?php echo number_format($sale['grand_total'], 2); ?></td>
                    <td data-label="Action">
                        <button class="btn btn-warning btn-sm edit-order" data-order-id="<?php echo $sale['order_id']; ?>" data-bs-toggle="modal" data-bs-target="#editOrderModal">Edit</button>
                        <a href="?delete=<?php echo $sale['order_id']; ?>&page=<?php echo $page; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Sales pagination">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderModalLabel">Edit Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="edit_order" value="1">
                        <input type="hidden" id="edit_order_id" name="order_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_order_date" class="form-label">Order Date</label>
                                    <input type="date" class="form-control" id="edit_order_date" name="order_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_client_name" class="form-label">Client Name</label>
                                    <input type="text" class="form-control" id="edit_client_name" name="client_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_client_contact" class="form-label">Client Contact</label>
                                    <input type="text" class="form-control" id="edit_client_contact" name="client_contact" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_sub_total" class="form-label">Sub Total</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_sub_total" name="sub_total" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_vat" class="form-label">VAT</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_vat" name="vat" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_total_amount" class="form-label">Total Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_total_amount" name="total_amount" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_discount" class="form-label">Discount</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_discount" name="discount" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_grand_total" class="form-label">Grand Total</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_grand_total" name="grand_total" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_paid" class="form-label">Paid</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_paid" name="paid" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_due" class="form-label">Due</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_due" name="due" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_payment_type" class="form-label">Payment Type</label>
                                    <input type="text" class="form-control" id="edit_payment_type" name="payment_type" value="Cash">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_payment_status" class="form-label">Payment Status</label>
                                    <input type="text" class="form-control" id="edit_payment_status" name="payment_status" value="Paid">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_payment_place" class="form-label">Payment Place</label>
                                    <input type="text" class="form-control" id="edit_payment_place" name="payment_place" value="Shop">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_gstn" class="form-label">GSTN</label>
                                    <input type="text" class="form-control" id="edit_gstn" name="gstn" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_order_status" class="form-label">Order Status</label>
                                    <select class="form-control" id="edit_order_status" name="order_status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h5>Order Items</h5>
                        <div id="edit-order-items">
                            <!-- Items will be populated dynamically -->
                        </div>
                        <button type="button" class="btn btn-secondary mb-3" id="edit-add-item">Add Item</button>
                        <button type="submit" class="btn btn-primary">Update Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('salesSearch');
    const salesTable = document.getElementById('salesTable');
    const rows = salesTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('input', function() {
        const searchTerm = searchInput.value.toLowerCase();

        for (let i = 0; i < rows.length; i++) {
            const orderId = rows[i].cells[0].textContent.toLowerCase();
            const clientName = rows[i].cells[2].textContent.toLowerCase();

            if (orderId.includes(searchTerm) || clientName.includes(searchTerm)) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    });
});
</script>


</body>
</html>