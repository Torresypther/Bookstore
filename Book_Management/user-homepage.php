<?php
include_once('connection.php');
session_start();  // Start the session to access user data

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: signup.php');  // Redirect to login page if not logged in
  exit();
}

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Determine which section to show based on query parameters
$show_home = isset($_GET['show_home']) && $_GET['show_home'] === 'true';
$show_books = isset($_GET['view_books']) && $_GET['view_books'] === 'true';

// Set Home as the default if no query parameters are provided
if (!$show_books && $logged_in) {
  $show_home = true;
}
$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID


$books = $pdo->query("SELECT * FROM books")->fetchAll(PDO::FETCH_ASSOC);

// If "book_id" is set in the URL, show the book details
$book_id = isset($_GET['book_id']) ? $_GET['book_id'] : null;

$userCheckStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$userCheckStmt->execute(['user_id' => $user_id]);

if ($userCheckStmt->rowCount() > 0) {
  // Proceed with adding to the cart
  if (isset($_POST['add_to_cart'])) {
    $book_id = $_POST['book_id'];

    // Check if the book already exists in the user's cart
    $checkCartStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = :user_id AND book_id = :book_id");
    $checkCartStmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);

    if ($checkCartStmt->rowCount() > 0) {
      // If the book is already in the cart, update the quantity
      $updateCartStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = :user_id AND book_id = :book_id");
      $updateCartStmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    } else {
      // If the book is not in the cart, insert it
      $insertCartStmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (:user_id, :book_id, 1)");
      $insertCartStmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
    }

    // Decrease the stock quantity in the books table by 1
    $updateBookStmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = :book_id AND stock > 0");
    $updateBookStmt->execute(['book_id' => $book_id]);

    // Set the session message indicating the product was successfully added
    $_SESSION['cart_success'] = 'The book has been added to your cart.';

    // Redirect to refresh the page and prevent resubmitting the form
    header("Location: " . $_SERVER['PHP_SELF'] . "?view_books=true");
    exit();
  }
}

function getCartItems($pdo, $user_id)
{
  try {
    // Query to fetch cart items along with book details
    $stmt = $pdo->prepare("
            SELECT b.title, b.price, b.image, c.quantity
            FROM cart c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.user_id = :user_id
        ");
    $stmt->execute(['user_id' => $user_id]);

    // Check if any rows are returned
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $cartItems; // Return all items as an associative array
  } catch (PDOException $e) {
    // Handle errors
    echo "Error fetching cart items: " . $e->getMessage();
    return [];
  }
}


$stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_items'] ?? 0;

// Check if "show_home" is set in the query string to show the homepage sections
$show_home = isset($_GET['show_home']) && $_GET['show_home'] == 'true';
// If "view_cart" parameter is set, show the cart content
$show_cart = isset($_GET['view_cart']) && $_GET['view_cart'] == 'true';
// Check if the 'view_books' query parameter is set
$show_books = isset($_GET['view_books']) && $_GET['view_books'] == 'true';


$searchQuery = $_GET['search_query'] ?? ''; // Fetch search query
$books = []; // Initialize empty array for books

try {
  if (!empty($searchQuery)) {
    // Search for books by title, author, or description
    $stmt = $pdo->prepare("SELECT * FROM books WHERE title LIKE :query OR author LIKE :query OR description LIKE :query");
    $stmt->execute(['query' => '%' . $searchQuery . '%']);
  } else {
    // Fetch all books if no search query is provided
    $stmt = $pdo->prepare("SELECT * FROM books");
    $stmt->execute();
  }
  $books = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all matching books
} catch (PDOException $e) {
  die("Error fetching books: " . $e->getMessage());
}


if (isset($_POST['checkout'])) {
  // Fetch the cart items from the database for the logged-in user
  $stmt = $pdo->prepare("SELECT c.book_id, c.quantity, b.title, b.stock, b.price
                         FROM cart c
                         JOIN books b ON c.book_id = b.book_id
                         WHERE c.user_id = :user_id");
  $stmt->execute(['user_id' => $user_id]);
  $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($cartItems)) {
    // If the cart is empty, display a message
    echo '<div class="alert alert-warning">Your cart is empty. Please add items to the cart before checking out.</div>';
  } else {
    // Start transaction
    try {
      $pdo->beginTransaction();

      $totalAmount = 0;
      $insufficientStockItems = []; // Array to store items with insufficient stock

      // Process each item in the cart
      foreach ($cartItems as $item) {
        if ($item['stock'] >= $item['quantity']) {
          // If stock is sufficient, deduct the stock
          $newStock = $item['stock'] - $item['quantity'];
          $updateBookStmt = $pdo->prepare("UPDATE books SET stock = :stock WHERE book_id = :book_id");
          $updateBookStmt->execute(['stock' => $newStock, 'book_id' => $item['book_id']]);

          // Add to total amount
          $totalAmount += $item['price'] * $item['quantity'];
        } else {
          // If stock is not enough, add the item to the insufficient stock list
          $insufficientStockItems[] = $item['title'];
        }
      }

      // Redirect to a confirmation page
      header('Location: order-confirmation.php');
      exit;
    } catch (PDOException $e) {
      // If there was an error during the transaction, roll back
      $pdo->rollBack();
      echo '<div class="alert alert-danger">Error processing your checkout. Please try again later.</div>';
    }
  }
}

if (isset($_POST['send_message'])) {
  // Get user data and message content
  $user_id = $_SESSION['user_id']; // Assuming session contains the logged-in user's ID
  $subject = $_POST['subject'];
  $content = $_POST['content'];

  // Prepare and insert message into database
  $stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, content, date_sent) VALUES (?, ?, ?, NOW())");
  $stmt->execute([$user_id, $subject, $content]);

  // Check if the message was successfully sent and trigger SweetAlert
  if ($stmt->rowCount() > 0) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Message Sent',
                    text: 'Your message has been successfully sent to the admin.',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
          </script>";
  } else {
    echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Message Failed',
                    text: 'There was an error sending your message. Please try again.',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
          </script>";
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Books and Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">

  <style>
    body {
      background-color: #FAD7A0;
      /* Peach */
      color: #4A4A4A;
      /* Gray for text readability */
      margin: 0;
      padding-top: 70px;
      /* Adjust based on the height of your navbar */
    }


    h5 {
      text-decoration: none;
    }

    .navbar-custom {
      background-color: #4A4A4A;
      /* Charcoal Gray */
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .navbar-custom .navbar-brand {
      font-size: 1.5rem;
      font-weight: 700;
      color: #F5DEB3;
      /* Wheat (Book-like contrast) */
      text-transform: uppercase;
    }

    .navbar-custom .nav-link {
      font-size: 1.4rem;
      font-weight: 500;
      margin-right: 10px;
      color: #fff;
      transition: color 0.3s;
    }

    .navbar-custom .nav-link:hover,
    .navbar-custom .nav-link.active {
      color: #ffc107;
    }

    .nav-item {
      display: flex;
      align-items: center;
      margin-top: 1rem;
    }

    .searchQ {
      display: flex;
      height: 30px;
      margin-top: 2.3rem;
      margin-right: 2rem;
    }

    .container h5 {
      font-size: 1.5em;
    }

    .hero-section {
      background: url('images/books.jpg');
      color: white;
      height: 100vh;
      text-align: center;
      box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.2);
      background-repeat: no-repeat;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .hero-section h1 {
      font-size: 3rem;
      font-weight: 700;
    }

    .hero-section p {
      font-size: 1.25rem;
      font-weight: 400;
      margin-bottom: 2rem;
    }

    .hero-btn {
      font-size: 1.2rem;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(to right, #ffc107, #ff9800);
      border: none;
      padding: 0.8rem 2rem;
      border-radius: 30px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hero-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
    }

    .card-title {
      font-family: 'Merriweather', serif;
      color: #4A4A4A;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin-bottom: 1rem;

    }

    .card-title:hover {
      cursor: default;
    }


    .btn-primary {
      background: linear-gradient(to right, #007bff, #0056b3);
      border: none;
      border-radius: 25px;
      padding: 0.6rem 1.2rem;
      font-weight: 600;
      color: #ffffff;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-primary:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-outline-primary {
      border: 2px solid #007bff;
      color: #007bff;
      border-radius: 25px;
      padding: 0.6rem 1.2rem;
      font-weight: 600;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-outline-primary:hover {
      background: #007bff;
      color: #ffffff;
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-danger {
      background: #e63946;
      border: none;
      border-radius: 25px;
      padding: 0.6rem 1.2rem;
      font-weight: 600;
      color: #ffffff;
    }

    .btn-danger:hover {
      background: #d62828;
    }

    .cart-summary {
      padding: 20px;
      background: #ffffff;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .cart-summary h2 {
      font-weight: 700;
      color: #0056b3;
      margin-bottom: 1rem;
    }

    .cart-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid #e9ecef;
    }

    .cart-items {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .cart-item {
      flex: 1 1 calc(30% - 20px);
      max-width: calc(20% - 20px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      padding: 15px;
      border-radius: 10px;
      background: #f8f9fa;
    }

    .cart-item img {
      max-width: 100%;
      height: auto;
      display: block;
      margin: 0 auto;
      border-radius: 10px;
    }

    .cart-total {
      font-size: 1.5rem;
      font-weight: 700;
      color: #28a745;
      text-align: right;
      margin-top: 1rem;
    }

    .btn-success {
      background: linear-gradient(to right, #28a745, #218838);
      border: none;
      color: #fff;
      padding: 0.6rem 1.5rem;
      font-size: 1.1rem;
      border-radius: 25px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-success:hover {
      transform: scale(1.01);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .cart-counter {
      background: red;
      color: white;
      border-radius: 50%;
      padding: 0.3rem 0.6rem;
      font-size: 0.9rem;
      font-weight: bold;
    }

    .card-body {
      font-family: 'Poppins', sans-serif;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }

    .modal-content {
      width: 300px;
    }

    @media (max-width: 768px) {
      .cart-item {
        flex: 1 1 100%;
        max-width: 100%;
      }

      .hero-section h1 {
        font-size: 2rem;
      }

      .hero-section p {
        font-size: 1rem;
      }
    }
  </style>


</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom navbar-dark fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Bookify</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <form class="searchQ" method="GET" action="">
            <input class="form-control me-2" type="search" name="search_query" placeholder="Search books" aria-label="Search" value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>">
            <button class="btn btn-outline-light" type="submit">Search</button>
          </form>

          <li class="nav-item">
            <a class="nav-link" href="?show_home=true">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="?view_books=true">Books</a>
          </li>
          <li class="nav-item">
    <a class="nav-link" href="?view_cart=true">
        <i class="fas fa-shopping-cart me-2"></i>Cart 
        <span class="cart-counter"><?= $cartCount ?></span>
    </a>
</li>

          <!-- Profile Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?= !empty($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'default-profile.png'; ?>"
                alt="Profile" class="rounded-circle ms-5" style="width: 50px; height: 50px; object-fit: cover;">
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
              <!-- Profile Link -->
              <li>
                <a class="dropdown-item" href="profile.php">
                  <i class="fas fa-user me-2"></i>Profile
                </a>
              </li>

              <!-- Divider -->
              <li>
                <hr class="dropdown-divider">
              </li>

              <!-- Contact Admin Modal Trigger -->
              <li>
                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contactModal">
                  <i class="fas fa-envelope me-2"></i>Contact Admin
                </button>
              </li>

              <!-- Divider -->
              <li>
                <hr class="dropdown-divider">
              </li>

              <!-- Logout -->
              <li>
                <a class="dropdown-item" href="#" onclick="confirmLogout()">
                  <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
              </li>
            </ul>
          </li>

        </ul>
      </div>
    </div>
  </nav>



  <!-- Sections -->
  <?php if ($show_home): ?>
    <div id="home" class="hero-section">
      <div class="container text-center text-white">
        <h1 class="hero-title">Welcome to Bookify</h1>
        <p class="hero-description">Your one-stop destination for books across various genres</p>
        <a href="?view_books=true" class="btn btn-light btn-lg hero-btn">Browse Books</a>
      </div>
    </div>
  <?php endif; ?>


  <!-- Books Section -->
  <?php if ($show_books): ?>
    <div class="container mt-4">
      <div class="row">
        <?php foreach ($books as $book) { ?>
          <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="card shadow-sm border-light h-100">
              <img src="./images/books/<?= $book['image'] ?>" class="card-img-top" style="height: 250px; object-fit: fill;" alt="Book Image">
              <div class="card-body text-center p-4 d-flex flex-column">
                <h5 class="card-title text-black mb-3"><?= htmlspecialchars($book['title']) ?></h5>
                <p class="card-text text-secondary"><span class="fw-semibold"><?= htmlspecialchars($book['author']) ?></span></p>
                <p class="card-text text-success fw-bold">₱<?= htmlspecialchars(number_format($book['price'], 2)) ?></p>
                <p class="card-text <?= $book['stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                  Stock: <?= htmlspecialchars($book['stock']) ?>
                </p>
                <?php if ($book['stock'] > 0): ?>
                  <form method="post" class="mt-auto">
                    <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                    <button type="submit" name="add_to_cart" class="btn btn-primary w-100 mb-2">Add to Cart</button>
                  </form>
                <?php else: ?>
                  <button type="button" class="btn btn-danger w-100 out-of-stock-btn" disabled>Out of Stock</button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#bookModal<?= $book['book_id'] ?>">
                  View Details
                </button>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  <?php endif; ?>


  <?php foreach ($books as $book) { ?>
    <div class="modal fade" id="bookModal<?= $book['book_id'] ?>" tabindex="-1" aria-labelledby="bookModalLabel<?= $book['book_id'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="bookModalLabel<?= $book['book_id'] ?>"><?= htmlspecialchars($book['title']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <img src="./images/books/<?= $book['image'] ?>" class="img-fluid mb-3" style="height: 250px; width:250px" alt="Book Image">
            <p><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
            <p><strong>Price:</strong> ₱<?= htmlspecialchars($book['price']) ?></p>
            <p><strong>Stock:</strong>
              <?php if ($book['stock'] > 0): ?>
                <span class="badge bg-success"><?= htmlspecialchars($book['stock']) ?> Available</span>
              <?php else: ?>
                <span class="badge bg-danger">Out of Stock</span>
              <?php endif; ?>
            </p>

            <!-- Description Information -->
            <p><strong>Description:</strong> <?= htmlspecialchars($book['description']) ?></p>
          </div>

          <div class="modal-footer">
            <form method="post">
              <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
              <!-- Disable the button if stock is zero -->
              <button type="submit" name="add_to_cart" class="btn btn-primary" <?= $book['stock'] == 0 ? 'disabled' : '' ?>>Add to Cart</button>
            </form>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  <?php } ?>



  <?php if ($show_cart): ?>
    <div class="cart-summary">
      <h2 class="mb-4 text-center">Your Cart</h2>
      <p class="text-center fs-5 text-secondary">Total Items: <strong><?= $cartCount ?></strong></p>

      <div class="cart-items">
        <?php
        // Fetch cart items and display them
        $cartItems = getCartItems($pdo, $user_id); // Assume this function retrieves cart items
        $totalCost = 0; // Initialize total cost

        foreach ($cartItems as $item):
          $itemTotalPrice = $item['price'] * $item['quantity']; // Calculate total price for each item
          $totalCost += $itemTotalPrice; // Add to total cost
        ?>
          <div class="cart-item d-flex align-items-center justify-content-between border rounded p-3 mb-3 shadow-sm">
            <div class="d-flex align-items-center">
              <?php
              $imagePath = !empty($item['image']) ? $item['image'] : 'images/default-placeholder.jpg';
              ?>
              <img src="images/books/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                class="cart-item-image rounded me-4" style="width: 80px; height: 120px; object-fit: cover;">
              <div>
                <h5 class="mb-2"><?= htmlspecialchars($item['title']) ?></h5>
                <p class="mb-1 text-secondary">Price: ₱<?= htmlspecialchars(number_format($item['price'], 2)) ?> x <?= htmlspecialchars($item['quantity']) ?></p>
                <p class="mb-0 text-success fw-bold">Total: ₱<?= htmlspecialchars(number_format($itemTotalPrice, 2)) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="cart-total mt-4 p-3 rounded bg-light shadow-sm">
        <h4 class="text-end text-dark">Grand Total: <span class="text-success">$<?= htmlspecialchars(number_format($totalCost, 2)) ?></span></h4>
      </div>

      <form method="POST" action="" class="d-flex justify-content-center">
        <button type="submit" name="checkout" class="btn btn-success w-50 mt-4 p-3 fs-5">Proceed to Checkout</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- Modal for Contacting Admin -->
  <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contactModalLabel">Contact Admin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="">
            <div class="mb-3">
              <label for="subject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="subject" name="subject" required>
            </div>
            <div class="mb-3">
              <label for="content" class="form-label">Message</label>
              <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" name="send_message">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php if ($searchQuery): ?>
    <div class="container mt-4">
      <h2 class="text-center">Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
      <?php if (empty($books)): ?>
        <p class="text-center text-muted">No books found.</p>
      <?php else: ?>
        <div class="row">
          <?php foreach ($books as $book): ?>
            <div class="col-md-4 mb-4">
              <div class="card shadow-sm border-light">
                <img src="./images/books/<?= $book['image'] ?>" class="card-img-top" style="height: 250px; object-fit: fill;" alt="Book Image">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                  <p class="card-text">Author: <?= htmlspecialchars($book['author']) ?></p>
                  <p class="card-text">Price: ₱<?= htmlspecialchars($book['price']) ?></p>
                  <form method="post" class="mb-2">
                    <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                    <?php if ($book['stock'] > 0): ?>
                      <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Add to Cart</button>
                    <?php else: ?>
                      <button type="button" class="btn btn-primary w-100" disabled>Out of Stock</button>
                    <?php endif; ?>
                  </form>
                  <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#bookModal<?= $book['book_id'] ?>">
                    View Details
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <?php if (isset($_SESSION['cart_success'])): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Book Added!',
        text: '<?= $_SESSION['cart_success'] ?>',
        showConfirmButton: false,
        timer: 2000,
        position: 'center'
      });
    </script>
    <?php unset($_SESSION['cart_success']); ?>
  <?php endif; ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Attach click event to all Out of Stock buttons
      const outOfStockButtons = document.querySelectorAll('.out-of-stock-btn');
      outOfStockButtons.forEach(button => {
        button.addEventListener('click', () => {
          Swal.fire({
            icon: 'error',
            title: 'Out of Stock',
            text: 'This book is currently unavailable.',
            confirmButtonColor: '#d33',
          });
        });
      });
    });
  </script>


  <script>
    function confirmLogout() {
      // Display SweetAlert confirmation
      Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to log out?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // Confirm button color
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'logout.php';
        }
      });
    }
  </script>

</body>

</html>