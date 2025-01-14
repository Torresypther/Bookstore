<?php
require_once('connection.php');

if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();   

        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    var_dump($order);
}
?>

<!-- Modal -->
<div class="modal fade" id="view-orders-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="view-orders-modalLabel">View Order Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <!-- Input Group for Order ID, User ID, and Book ID -->
                    <div class="input-group">
                        <div class="form-group">
                            <label for="order-id">Order ID: </label>
                            <input type="number" id="order-id" value="<?= isset($order['order_id']) ? htmlspecialchars($order['order_id']) : ''; ?>" disabled>

                        </div>
                        <div class="form-group">
                            <label for="user-id">User ID: </label>
                            <input type="number" id="user-id" value="<?= isset($order['user_id']) ? htmlspecialchars($order['user_id']) : ''; ?>" disabled>

                        </div>
                        <div class="form-group">
                            <label for="book-id">Book ID: </label>
                            <input type="number" id="book-id" value="<?= isset($order['book_id']) ? htmlspecialchars($order['book_id']) : ''; ?>" disabled>

                        </div>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity: </label>
                        <input type="number" id="quantity" value="<?= htmlspecialchars($order['quantity']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="price">Total Amount: </label>
                        <input type="number" id="price" value="₱<?= number_format($order['price'], 2); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="order-status">Order Status: </label>
                        <select name="status" id="order-status">
                            <option value="pending" <?= ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="shipped out" <?= ($order['status'] == 'shipped out') ? 'selected' : ''; ?>>Shipped Out</option>
                            <option value="cancelled" <?= ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>


<style>
    /* Modal Styling */
    .modal-content {
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        background-color: #f9f9f9;
        max-width: 800px;
        /* Increased width for a wider body */
        margin: auto;
    }

    /* Modal Header */
    .modal-header {
        border-bottom: 1px solid #ddd;
        background-color: #503b31;
        color: #f7f5ef;
        padding: 10px 15px;
    }

    .modal-header .btn-close {
        color: white;
        background-color: transparent;
        border: none;
        font-size: 1.5rem;
    }

    .modal-header h1 {
        font-size: 1.25rem;
        margin: 0;
        color: #f7f5ef;
    }

    /* Modal Body */
    .modal-body {
        padding: 20px;
        background-color: #ffffff;
        border-radius: 10px;
    }

    /* Input Group for Order ID, User ID, Book ID */
    .modal-body .input-group {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        /* Space between the inputs */
        margin-bottom: 20px;
        /* Spacing between the input group and other fields */
    }

    /* Form Labels */
    .modal-body label {
        font-size: 1rem;
        margin-bottom: 5px;
        display: block;
        font-weight: bold;
        color: #333;
    }

    /* Form Inputs */
    .modal-body input,
    .modal-body select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #f1f1f1;
    }

    /* Adjust the width of each input in the input group */
    .modal-body .input-group .form-group {
        flex: 1;
    }

    /* Set each input's width to 30% */
    .modal-body input {
        width: 100%;
    }

    /* Placeholder Styling */
    .modal-body input::placeholder {
        color: #aaa;
    }

    /* Select Styling */
    .modal-body select {
        padding: 10px;
        background-color: #f1f1f1;
    }

    /* Modal Footer */
    .modal-footer {
        padding: 10px 15px;
        border-top: 1px solid #ddd;
        background-color: #f1f1f1;
        text-align: right;
    }

    /* Buttons */
    .modal-footer .btn {
        padding: 10px 20px;
        font-size: 1rem;
        border-radius: 5px;
        margin: 5px;
        transition: background-color 0.3s ease-in-out;
        border-color: none;
    }

    .modal-footer .btn-secondary {
        background-color: rgb(79, 85, 90);
        color: white;
    }

    .modal-footer .btn-primary {
        background-color: rgb(90, 199, 87);
        color: white;
    }

    .modal-footer .btn-secondary:hover {
        background-color: rgb(67, 76, 82);
    }

    .modal-footer .btn-primary:hover {
        background-color: rgb(80, 176, 77);
    }

    /* Responsive Design for Modal */
    @media (max-width: 768px) {
        .modal-dialog {
            width: 90%;
        }

        .modal-header h1 {
            font-size: 1.1rem;
        }

        .modal-body input,
        .modal-body select {
            font-size: 0.9rem;
        }

        .modal-footer .btn {
            font-size: 0.9rem;
            padding: 8px 15px;
        }

        /* Stack the fields vertically on smaller screens */
        .modal-body .input-group {
            flex-direction: column;
            gap: 10px;
            /* Vertical spacing between fields */
        }

        .modal-body input {
            width: 100%;
            /* Full width for each input when stacked */
        }
    }
</style>