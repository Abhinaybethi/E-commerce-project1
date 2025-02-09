<?php
session_start();
include '../includes/db.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<h3 style='text-align: center; color: red;'>Your cart is empty. <a href='cart.php'>Go back to cart</a></h3>";
    exit();
}

// Retrieve cart and calculate total
$cart = $_SESSION['cart'];
$totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Handle order submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $paymentMethod = $_POST['payment_method'];

    // Validate inputs
    if (empty($fullName) || empty($email) || empty($address) || empty($paymentMethod)) {
        echo "<p class='text-danger'>All fields are required!</p>";
    } else {
        try {
            // Insert order into orders table
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, fullname, email, address, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $fullName, $email, $address, $totalAmount, $paymentMethod]);
            
            
            $orderId = $pdo->lastInsertId();

            // Insert cart items into order_items table
            foreach ($cart as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            }

            // Clear cart
            unset($_SESSION['cart']);

            // Redirect to order success page
            header("Location: order_success.php?order_id=$orderId");
            exit();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center">Checkout</h2>

    <h4>Order Summary</h4>
    <ul class="list-group mb-3">
        <?php foreach ($cart as $item) : ?>
            <li class="list-group-item d-flex justify-content-between">
                <?= htmlspecialchars($item['name']); ?> (x<?= $item['quantity']; ?>)
                <span>$<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
            </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between">
            <strong>Total</strong>
            <strong>$<?= number_format($totalAmount, 2); ?></strong>
        </li>
    </ul>

    <form action="" method="POST">
        <h4>Billing Details</h4>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="fullname" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Shipping Address</label>
            <textarea class="form-control" name="address" rows="3" required></textarea>
        </div>

        <h4>Payment Method</h4>
        <select class="form-control mb-3" name="payment_method" required>
            <option value="credit_card">Credit Card</option>
            <option value="paypal">PayPal</option>
            <option value="cod">Cash on Delivery</option>
        </select>

        <button type="submit" class="btn btn-primary w-100">Place Order</button>
    </form>
</div>
</body>
</html>
