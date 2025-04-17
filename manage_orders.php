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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    $order_id = createOrder($pdo, $order_date, $client_name, $client_contact, $sub_total, $vat, $total_amount, $discount, $grand_total, $paid, $due, $payment_type, $payment_status, $payment_place, $gstn, $order_status, $user_id);

    // Add order items
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $rates = $_POST['rate'];
    $totals = $_POST['total'];

    for ($i = 0; $i < count($product_ids); $i++) {
        addOrderItem($pdo, $order_id, $product_ids[$i], $quantities[$i], $rates[$i], $totals[$i], 1);
    }

    header("Location: manage_orders.php");
    exit();
}

if (isset($_GET['delete'])) {
    deleteOrder($pdo, $_GET['delete']);
    header("Location: manage_orders.php");
    exit();
}

$orders = getOrders($pdo);
$products = getProducts($pdo);
?>
 <style>
        .search-container { margin-bottom: 20px; }
        .pagination-container { margin-top: 20px; }
        
    </style>
<div class="flex-grow-1 p-4">
    <h2>Manage Orders</h2>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addOrderModal">Add Order</button>

    <!---table ---->
    <div class="container">
        <!-- Search Bar -->
        <div class="search-container" style="width:450px;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search orders by client name or contact...">
        </div>

        <!-- Order Table -->
        <table class="table table-bordered" id="orderTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client Name</th>
                    <th>Contact</th>
                    <th>Grand Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="orderTableBody">
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td data-label="Date"><?php echo $order['order_date']; ?></td>
                        <td data-label="Client Name"><?php echo $order['client_name']; ?></td>
                        <td data-label="Contact"><?php echo $order['client_contact']; ?></td>
                        <td data-label="Grand Total">UGX <?php echo number_format($order['grand_total'], 2); ?></td>
                        <td data-label="Paid">UGX <?php echo number_format($order['paid'], 2); ?></td>
                        <td data-label="Due">UGX <?php echo number_format($order['due'], 2); ?></td>
                        <td data-label="Action">
                            <a href="?delete=<?php echo $order['order_id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            <button class="btn btn-info btn-sm view-items" data-order-id="<?php echo $order['order_id']; ?>" data-bs-toggle="modal" data-bs-target="#viewItemsModal">View Items</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination-container">
            <nav>
                <ul class="pagination" id="pagination">
                    <!-- Pagination links will be generated by JavaScript -->
                </ul>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const rowsPerPage = 5;
        let currentPage = 1;
        const table = document.getElementById('orderTable');
        const tbody = document.getElementById('orderTableBody');
        const rows = Array.from(tbody.getElementsByTagName('tr'));
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');

        function displayRows(filteredRows) {
            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            // Update table rows
            tbody.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            filteredRows.slice(start, end).forEach(row => tbody.appendChild(row));

            // Update pagination
            pagination.innerHTML = '';
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.textContent = i;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    displayRows(filteredRows);
                });
                li.appendChild(a);
                pagination.appendChild(li);
            }
        }

        // Search functionality
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const filteredRows = rows.filter(row => {
                const clientName = row.cells[1].textContent.toLowerCase();
                const contact = row.cells[2].textContent.toLowerCase();
                return clientName.includes(searchTerm) || contact.includes(searchTerm);
            });
            currentPage = 1; // Reset to first page
            displayRows(filteredRows);
        });

        // Previous and Next buttons
        pagination.insertAdjacentHTML('beforeend', `
            <li class="page-item">
                <a class="page-link" href="#" id="prevPage">Previous</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="#" id="nextPage">Next</a>
            </li>
        `);

        document.getElementById('prevPage').addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                displayRows(rows);
            }
        });

        document.getElementById('nextPage').addEventListener('click', (e) => {
            e.preventDefault();
            const totalPages = Math.ceil(rows.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                displayRows(rows);
            }
        });

        // Initial display
        displayRows(rows);
    </script>
    <!---table--view-->
    <!-- Add Order Modal -->
    <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOrderModalLabel">Add Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="order_date" class="form-label">Order Date</label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="client_name" class="form-label">Client Name</label>
                                    <input type="text"  placeholder="E.g Clinton Olot" class="form-control" id="client_name" name="client_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="client_contact" class="form-label">Client Contact</label>
                                    <input type="text" placeholder="E.g 0763828117"class="form-control" id="client_contact" name="client_contact" required>
                                </div>
                                <div class="mb-3">
                                    <label for="sub_total" class="form-label">Sub Total</label>
                                    <input type="number" step="0.01" class="form-control" id="sub_total" name="sub_total" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="vat" class="form-label">VAT</label>
                                    <input type="number" step="0.01" class="form-control" id="vat" name="vat" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Total Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount" class="form-label">Discount</label>
                                    <input type="number" step="0.01" class="form-control" id="discount" name="discount" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="grand_total" class="form-label">Grand Total</label>
                                    <input type="number" step="0.01" class="form-control" id="grand_total" name="grand_total" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="paid" class="form-label">Paid</label>
                                    <input type="number" step="0.01" class="form-control" id="paid" name="paid" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="due" class="form-label">Due</label>
                                    <input type="number" step="0.01" class="form-control" id="due" name="due" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="payment_type" class="form-label">Payment Type</label>
                                    <input type="text" class="form-control" id="payment_type" name="payment_type" value="Cash">
                                </div>
                                <div class="mb-3">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <input type="text" class="form-control" id="payment_status" name="payment_status" value="Paid">
                                </div>
                                <div class="mb-3">
                                    <label for="payment_place" class="form-label">Payment Place</label>
                                    <input type="text" class="form-control" id="payment_place" name="payment_place" value="Shop">
                                </div>
                                <div class="mb-3">
                                    <label for="gstn" class="form-label">GSTN</label>
                                    <input type="text" class="form-control" id="gstn" name="gstn" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="order_status" class="form-label">Order Status</label>
                                    <select class="form-control" id="order_status" name="order_status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h5>Order Items</h5>
                        <div id="order-items">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="product_id" class="form-label">Product</label>
                                    <select class="form-control" name="product_id[]">
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['product_id']; ?>" data-rate="<?php echo $product['selling_price']; ?>"><?php echo $product['product_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control quantity" name="quantity[]" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="rate" class="form-label">Rate</label>
                                    <input type="number" step="0.01" class="form-control rate" name="rate[]" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label for="total" class="form-label">Total</label>
                                    <input type="number" step="0.01" class="form-control total" name="total[]" readonly>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary mb-3" id="add-item">Add Item</button>
                        <button type="submit" class="btn btn-primary">Create Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Items Modal -->
    <div class="modal fade" id="viewItemsModal" tabindex="-1" aria-labelledby="viewItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewItemsModalLabel">Order Items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Rate</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="order-items-table">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


</body>
</html>