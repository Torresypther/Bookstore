<?php
include_once('connection.php');
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-up.php');
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

// Function to fetch order details
function getOrderDetails($pdo, $user_id)
{
    try {
        // Query to fetch order details
        $stmt = $pdo->prepare("
            SELECT b.title, b.price, o.quantity, o.order_date
            FROM orders o
            JOIN books b ON o.book_id = b.book_id
            WHERE o.user_id = :user_id
            AND o.status = 'Completed'
            ORDER BY o.order_date DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching order details: " . $e->getMessage();
        return [];
    }
}

// Get the most recent order details
$orderItems = getOrderDetails($pdo, $user_id);
$totalAmount = 0;
foreach ($orderItems as $item) {
    $totalAmount += $item['quantity'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .order-summary-table th, .order-summary-table td {
            text-align: center;
        }
        .order-total {
            font-size: 1.5em;
            font-weight: bold;
        }
        .order-success-message {
            text-align: center;
            margin-top: 30px;
            font-size: 1.25em;
            font-weight: bold;
        }
        .continue-btn {
            margin-top: 20px;
            text-align: center;
        }
        .order-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9em;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Order Success Message -->
            <div class="order-success-message alert alert-success">
                <p>Your order has been placed successfully! Thank you for your purchase.</p>
            </div>

            <!-- Order Summary -->
            <h3 class="text-center mb-4">Order Summary</h3>

            <div class="card">
                <div class="card-body">
                    <?php if (!empty($orderItems)): ?>
                        <table class="table table-striped order-summary-table">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p class="order-total text-center">
                            <strong>Total Amount: ₱<?php echo number_format($totalAmount, 2); ?></strong>
                        </p>
                    <?php else: ?>
                        <p class="text-center">You have no completed orders yet.</p>
                    <?php endif; ?>
                    
                    <!-- Continue Shopping Button -->
                    <div class="continue-btn">
                        <a href="user-homepage.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>

            <!-- Footer Information -->
            <div class="order-footer">
                <p>If you have any questions, please contact our support team.</p>
                <p>Thank you for shopping with us!</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
