<?php
function getProducts($pdo) {
    $stmt = $pdo->query("
        SELECT p.*, b.brand_name, c.categories_name 
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN categories c ON p.categories_id = c.categories_id
        WHERE p.active = 1 AND p.status = 1
    ");
    return $stmt->fetchAll();
}

function addProduct($pdo, $product_name, $product_image, $brand_id, $categories_id, $quantity, $selling_price, $rate, $active, $status) {
    $stmt = $pdo->prepare("
        INSERT INTO products (product_name, product_image, brand_id, categories_id, quantity, selling_price, rate, active, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$product_name, $product_image, $brand_id, $categories_id, $quantity, $selling_price, $rate, $active, $status]);
}

function importProductsFromCSV($pdo, $file) {
    // Assuming this function exists and works correctly
    $handle = fopen($file, 'r');
    fgetcsv($handle); // Skip header row
    while (($data = fgetcsv($handle)) !== false) {
        $stmt = $pdo->prepare("
            INSERT INTO products (product_name, product_image, brand_id, categories_id, quantity, selling_price, rate, active, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute($data);
    }
    fclose($handle);
}

function deleteProduct($pdo, $product_id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
}

function updateProductQuantity($pdo, $product_id, $quantity_change) {
    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $current_quantity = $stmt->fetchColumn();

    $new_quantity = $current_quantity - $quantity_change;
    if ($new_quantity < 0) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
    $stmt->execute([$new_quantity, $product_id]);
    return true;
}

function checkProductStock($pdo, $product_id, $required_quantity) {
    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $current_quantity = $stmt->fetchColumn();
    return $current_quantity >= $required_quantity;
}
?>