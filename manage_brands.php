<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/config.php';
require_once '../functions/brand.php';
include '../includes/header.php';
include '../includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand_name = $_POST['brand_name'];
    $brand_active = $_POST['brand_active'];
    $brand_status = $_POST['brand_status'];
    addBrand($pdo, $brand_name, $brand_active, $brand_status);
    header("Location: manage_brands.php");
    exit();
}

if (isset($_GET['delete'])) {
    deleteBrand($pdo, $_GET['delete']);
    header("Location: manage_brands.php");
    exit();
}

$brands = getBrands($pdo);
?>

<div class="flex-grow-1 p-4">
    <h2>Manage Brands</h2>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addBrandModal">Add Brand</button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Brand ID</th>
                <th>Brand Name</th>
                <th>Active</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($brands as $brand): ?>
                <tr>
                    <td><?php echo $brand['brand_id']; ?></td>
                    <td><?php echo $brand['brand_name']; ?></td>
                    <td><?php echo $brand['brand_active'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $brand['brand_status'] ? 'Active' : 'Inactive'; ?></td>
                    <td>
                        <a href="?delete=<?php echo $brand['brand_id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Brand Modal -->
    <div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandModalLabel">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="brand_name" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="brand_name" name="brand_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="brand_active" class="form-label">Active</label>
                            <select class="form-control" id="brand_active" name="brand_active">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="brand_status" class="form-label">Status</label>
                            <select class="form-control" id="brand_status" name="brand_status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Brand</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>