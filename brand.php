
<?php
function addBrand($pdo, $brand_name, $brand_active, $brand_status) {
    $stmt = $pdo->prepare("INSERT INTO brands (brand_name, brand_active, brand_status) VALUES (?, ?, ?)");
    return $stmt->execute([$brand_name, $brand_active, $brand_status]);
}

function getBrands($pdo) {
    $stmt = $pdo->query("SELECT * FROM brands");
    return $stmt->fetchAll();
}

function deleteBrand($pdo, $brand_id) {
    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Check for dependent products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
        $stmt->execute([$brand_id]);
        $product_count = $stmt->fetchColumn();

        // Delete associated products
        $stmt = $pdo->prepare("DELETE FROM products WHERE brand_id = ?");
        $stmt->execute([$brand_id]);

        // Delete the brand
        $stmt = $pdo->prepare("DELETE FROM brands WHERE brand_id = ?");
        $stmt->execute([$brand_id]);

        // Commit the transaction
        $pdo->commit();

        return [
            'success' => true,
            'message' => "Brand and $product_count associated product(s) deleted successfully."
        ];
    } catch (PDOException $e) {
        // Roll back the transaction on error
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => "Error deleting brand: " . $e->getMessage()
        ];
    }
}
?>