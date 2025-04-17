<?php
function addCategory($pdo, $categories_name, $categories_active, $categories_status) {
    $stmt = $pdo->prepare("INSERT INTO categories (categories_name, categories_active, categories_status) VALUES (?, ?, ?)");
    return $stmt->execute([$categories_name, $categories_active, $categories_status]);
}

function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories");
    return $stmt->fetchAll();
}

function deleteCategory($pdo, $categories_id) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE categories_id = ?");
    return $stmt->execute([$categories_id]);
}
?>