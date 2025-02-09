<?php
session_start();
include '../includes/db.php'; // Ensure this correctly initializes a PDO connection

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ✅ Handle adding products to the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    $productQuantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Fetch product details from database using PDO
    $query = "SELECT * FROM products WHERE id = :id"; 
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC); // Correct way to fetch result

    if ($product) {
        $productName = $product['name'];
        $productPrice = (float)$product['price'];

        // Check if product already exists in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $productId) {
                $item['quantity'] += $productQuantity;
                $item['total_price'] = $item['quantity'] * $item['price']; // Update total price
                $found = true;
                break;
            }
        }

        // If product is not in cart, add new item
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $productId,
                'name' => $productName,
                'price' => $productPrice,
                'quantity' => $productQuantity,
                'total_price' => $productPrice * $productQuantity
            ];
        }
    } else {
        die("Error: Product not found.");
    }

    header("Location: cart.php");
    exit();
}

// ✅ Handle updating quantity in cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quantity'])) {
    $productId = $_POST['update_quantity'];
    $newQuantity = isset($_POST['new_quantity']) ? (int)$_POST['new_quantity'] : 1;

    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $productId) {
            $item['quantity'] = $newQuantity;
            $item['total_price'] = $item['quantity'] * $item['price']; // Update total price
            break;
        }
    }

    header("Location: cart.php");
    exit();
}

// ✅ Handle removing an item from the cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $productId = $_GET['remove'];

    $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($productId) {
        return $item['id'] != $productId;
    });

    header("Location: cart.php");
    exit();
}

// ✅ Handle clearing the cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container {
            max-width: 800px; margin: 40px auto; background: #fff;
            padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .table th { background-color: #343a40; color: white; }
        .btn-success { background-color: #28a745; border: none; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #ffc107; color: black; }

                    /* Center the empty cart container */
/* Full-page center alignment */
.empty-cart-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh; /* Makes it take the full height of the viewport */
    background-color: #f8f9fa; /* Light gray background for contrast */
}

/* Styled empty cart box */
.empty-cart {
    text-align: center;
    background: white;
    border-radius: 12px;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
    padding: 40px;
    width: 100%;
    max-width: 400px; /* Keeps it compact and elegant */
}

/* Shopping Cart Title */
.cart-title {
    font-size: 28px;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

/* Empty Cart Message */
.cart-message {
    font-size: 18px;
    color: #ff4d4d; /* A modern red */
    font-weight: 500;
    margin-bottom: 20px;
}

/* Styled Continue Shopping Button */
.continue-btn {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: bold;
    color: white;
    background: #007bff; /* Vibrant blue */
    border: none;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease-in-out;
    display: inline-block;
    box-shadow: 0px 4px 10px rgba(0, 123, 255, 0.2);
}

.continue-btn:hover {
    background: #0056b3;
    transform: scale(1.05);
}


    </style>
</head>
<body>
<div class="container">
    <h2>Shopping Cart</h2>
    <?php if (empty($_SESSION['cart'])) : ?>
        <p class="text-danger">Your cart is empty.</p>
        <a href="../index.php" class="btn btn-primary mt-3">Continue Shopping</a>
    <?php else : ?>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $totalAmount = 0; ?>
                <?php foreach ($_SESSION['cart'] as &$item) : ?>
                    <?php 
                        $itemTotal = (float)$item['price'] * (int)$item['quantity']; 
                        $totalAmount += $itemTotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']); ?></td>
                        <td>$<?= number_format((float)$item['price'], 2); ?></td>
                        <td>
                            <form method="post" action="cart.php">
                                <input type="hidden" name="update_quantity" value="<?= $item['id']; ?>">
                                <input type="number" name="new_quantity" value="<?= (int)$item['quantity']; ?>" min="1" class="form-control text-center" style="width: 70px; display: inline-block;">
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                        <td>$<?= number_format($itemTotal, 2); ?></td>
                        <td>
                            <a href="cart.php?remove=<?= $item['id']; ?>" class="btn btn-danger btn-sm">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total:</th>
                    <th>$<?= number_format($totalAmount, 2); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <div class="d-flex justify-content-between">
            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            <a href="../index.php?clear=true" class="btn btn-warning">Clear Cart</a>
            <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
