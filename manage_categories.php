<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../includes/config.php';
require_once '../functions/category.php';
include '../includes/header.php';
include '../includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categories_name = $_POST['categories_name'];
    $categories_active = $_POST['categories_active'];
    $categories_status = $_POST['categories_status'];
    addCategory($pdo, $categories_name, $categories_active, $categories_status);
    header("Location: manage_categories.php");
    exit();
}

if (isset($_GET['delete'])) {
    deleteCategory($pdo, $_GET['delete']);
    header("Location: manage_categories.php");
    exit();
}

$categories = getCategories($pdo);
?>

<div class="flex-grow-1 p-4">
    <h2>Manage Categories</h2>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add Category</button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category ID</th>
                <th>Category Name</th>
                <th>Active</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo $category['categories_id']; ?></td>
                    <td><?php echo $category['categories_name']; ?></td>
                    <td><?php echo $category['categories_active'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $category['categories_status'] ? 'Active' : 'Inactive'; ?></td>
                    <td>
                        <a href="?delete=<?php echo $category['categories_id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="categories_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="categories_name" name="categories_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="categories_active" class="form-label">Active</label>
                            <select class="form-control" id="categories_active" name="categories_active">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="categories_status" class="form-label">Status</label>
                            <select class="form-control" id="categories_status" name="categories_status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>