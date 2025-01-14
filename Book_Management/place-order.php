<?php
include_once('connection.php');
session_start(); // Start session to access user data

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.php');  // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];  // Get user ID from session

// Fetch cart items before placing the order
$stmt = $pdo->prepare("SELECT c.book_id, c.quantity, b.price
                       FROM cart c
                       JOIN books b ON c.book_id = b.book_id
                       WHERE c.user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total amount for the order
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['quantity'] * $item['price'];
}

// Start transaction
try {
    $pdo->beginTransaction();

    // Insert the order into the sales table
    $insertSale = $pdo->prepare("INSERT INTO sales (user_id, total_amount, sale_date) 
                                 VALUES (:user_id, :total_amount, NOW())");
    $insertSale->execute([
        'user_id' => $user_id,
        'total_amount' => $totalAmount
    ]);

    // Get the sale_id of the newly inserted sale
    $saleId = $pdo->lastInsertId();

    // Process each cart item (e.g., update stock, create order)
    foreach ($cartItems as $item) {
        $bookId = $item['book_id'];
        $quantity = $item['quantity'];

        // Update book stock
        $updateStock = $pdo->prepare("UPDATE books SET stock = stock - :quantity WHERE book_id = :book_id");
        $updateStock->execute(['quantity' => $quantity, 'book_id' => $bookId]);

        // Insert into order history (this assumes an 'orders' table exists)
        $insertOrder = $pdo->prepare("INSERT INTO orders (user_id, book_id, quantity, price, sale_id) 
                                      VALUES (:user_id, :book_id, :quantity, :price, :sale_id)");
        $insertOrder->execute([
            'user_id' => $user_id,
            'book_id' => $bookId,
            'quantity' => $quantity,
            'price' => $item['price'],
            'sale_id' => $saleId
        ]);
    }

    // Clear the cart
    $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
    $clearCart->execute(['user_id' => $user_id]);

    // Commit transaction
    $pdo->commit();

    // Redirect to a confirmation page
    $_SESSION['checkout_success'] = "Your order has been placed successfully!";
    header('Location: order-success.php');
    exit();

} catch (PDOException $e) {
    // Rollback transaction if any error occurs
    $pdo->rollBack();
    echo "Error placing order: " . $e->getMessage();
}
