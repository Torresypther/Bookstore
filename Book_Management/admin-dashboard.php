  <?php
  session_start();

  include_once('connection.php');

  // Ensure the user is logged in and is an admin
  if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: signup.php');
    exit();
  }

  try {
    // Fetch books with a JOIN on categories, sorted by category_id
    $stmt = $pdo->prepare(" SELECT 
            b.book_id, 
            b.title, 
            b.author, 
            b.book_number, 
            b.price, 
            b.stock, 
            b.description, 
            b.image, 
            c.name AS category 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.category_id 
        ORDER BY b.book_id DESC
    ");
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
  }

  // Fetch orders from the database
  try {
    $query = "SELECT * FROM orders ORDER BY order_date DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    echo "Error fetching orders: " . $e->getMessage();
    exit;
  }

  $query = " SELECT 
        orders.order_id, 
        users.name AS customer_name, 
        books.title AS book_title, 
        orders.book_id, 
        orders.quantity, 
        orders.price, 
        orders.order_date, 
        orders.status
    FROM orders
    JOIN users ON orders.user_id = users.user_id
    JOIN books ON orders.book_id = books.book_id
";

  try {
    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    // Fetch all orders
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    echo 'Query failed: ' . $e->getMessage();
  }

  try {
    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    // Fetch all orders
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    echo 'Query failed: ' . $e->getMessage();
  }

  // Handle Add Category functionality
  if (isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    $stmt->execute(['name' => $name, 'description' => $description]);

    $_SESSION['message'] = 'Category added successfully!';
    header('Location: admin-dashboard.php');
    exit();
  }

  // Handle Delete Category functionality
  if (isset($_GET['delete_category'])) {
    $category_id = $_GET['delete_category'];

    // Check if there are books in this category
    $bookCheckStmt = $pdo->prepare("SELECT COUNT(*) AS count FROM books WHERE category_id = :category_id");
    $bookCheckStmt->execute(['category_id' => $category_id]);
    $bookCount = $bookCheckStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($bookCount > 0) {
      $_SESSION['message'] = 'Cannot delete category as it contains books.';
    } else {
      // Delete the category if no books are associated
      $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = :category_id");
      $stmt->execute(['category_id' => $category_id]);
      $_SESSION['message'] = 'Category deleted successfully!';
    }

    // Redirect back to the admin dashboard
    header('Location: admin-dashboard.php');
    exit();
  }

  // Handling Add Book functionality
  if (isset($_POST['addBook'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category'];
    $description = $_POST['description'];

    // Handling Image Upload
    $imageName = 'default.jpg'; // Default image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $imageName = basename($_FILES['image']['name']);
      $imageTmpName = $_FILES['image']['tmp_name'];
      $imageDestination = './images/books/' . $imageName;

      if (!move_uploaded_file($imageTmpName, $imageDestination)) {
        $_SESSION['message'] = 'Error uploading the image.';
        header('Location: admin-dashboard.php');
        exit();
      }
    }

    // Insert the book into the database
    $stmt = $pdo->prepare("INSERT INTO books (title, author, price, stock, category_id, description, image) 
                              VALUES (:title, :author, :price, :stock, :category_id, :description, :image)");
    $stmt->execute([
      'title' => $title,
      'author' => $author,
      'price' => $price,
      'stock' => $stock,
      'category_id' => $category_id,
      'description' => $description,
      'image' => $imageName
    ]);

    $_SESSION['message'] = 'Book added successfully!';
    header('Location: admin-dashboard.php');
    exit();
  }

  // Handle Update Book functionality
  if (isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];

    // Handling Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $imageName = $_FILES['image']['name'];
      $imageTmpName = $_FILES['image']['tmp_name'];
      $imageDestination = './images/books/' . $imageName;
      move_uploaded_file($imageTmpName, $imageDestination);

      // Update book details including the image
      $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, price = :price, 
                                stock = :stock, category_id = :category_id, description = :description, 
                                image = :image WHERE book_id = :book_id");
      $stmt->execute([
        'title' => $title,
        'author' => $author,
        'price' => $price,
        'stock' => $stock,
        'category_id' => $category_id,
        'description' => $description,
        'image' => $imageName,
        'book_id' => $book_id
      ]);
    } else {
      // Update book details without changing the image
      $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, price = :price, 
                                stock = :stock, category_id = :category_id, description = :description 
                                WHERE book_id = :book_id");
      $stmt->execute([
        'title' => $title,
        'author' => $author,
        'price' => $price,
        'stock' => $stock,
        'category_id' => $category_id,
        'description' => $description,
        'book_id' => $book_id
      ]);
    }

    $_SESSION['message'] = 'Book updated successfully!';
    header('Location: admin-dashboard.php');
    exit();
  }

  // Handling Delete Book functionality
  if (isset($_GET['delete_book'])) {
    $book_id = $_GET['delete_book'];

    // Get the image name from the database to delete the file
    $stmt = $pdo->prepare("SELECT image FROM books WHERE book_id = :book_id");
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
      $imagePath = './images/books/' . $book['image'];
      if (file_exists($imagePath)) {
        unlink($imagePath); // Delete the image file
      }

      // Delete the book from the database
      $deleteStmt = $pdo->prepare("DELETE FROM books WHERE book_id = :book_id");
      $deleteStmt->execute(['book_id' => $book_id]);

      $_SESSION['message'] = 'Book deleted successfully!';
    }

    header('Location: admin-dashboard.php');
    exit();
  }


  if (isset($_GET['edit_book'])): ?>
    <?php
    $book_id = $_GET['edit_book'];
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :book_id");
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
  <?php endif;

  // Get the current date, month, and year
  $currentDate = date('Y-m-d');
  $currentMonth = date('m');
  $currentYear = date('Y');

  try {
    // Query for daily sales
    $dailyStmt = $pdo->prepare("SELECT * FROM sales WHERE DATE(sale_date) = :date");
    $dailyStmt->bindParam(':date', $currentDate, PDO::PARAM_STR);
    $dailyStmt->execute();
    $dailySales = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Query for monthly sales
    $monthlyStmt = $pdo->prepare("SELECT * FROM sales WHERE MONTH(sale_date) = :month AND YEAR(sale_date) = :year");
    $monthlyStmt->bindParam(':month', $currentMonth, PDO::PARAM_INT);
    $monthlyStmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $monthlyStmt->execute();
    $monthlySales = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Query for yearly sales
    $yearlyStmt = $pdo->prepare("SELECT * FROM sales WHERE YEAR(sale_date) = :year");
    $yearlyStmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $yearlyStmt->execute();
    $yearlySales = $yearlyStmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    // Handle the exception
    echo "Error: " . $e->getMessage();
    $dailySales = $monthlySales = $yearlySales = false; // Set to false if queries fail
  }

  try {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY category_id ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_OBJ);  // Fetch as objects
  } catch (PDOException $e) {
    die("Failed to fetch categories: " . $e->getMessage());
  }
  ?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/line-awesome/dist/line-awesome/css/line-awesome.min.css">

    <style>
      @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

      ::after,
      ::before {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      a,
      button {
        text-decoration: none;
        width: 125px;
        margin-bottom: 5px;
      }

      li {
        list-style: none;
      }

      h1 {
        margin: 10px;
        font-weight: 600;
        font-size: 1.5rem;
        color: #2d6a4f;
        /* Added a green color for the header */
      }

      body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f9f4;
        /* Lighter greenish background for a fresh look */
      }

      .wrapper {
        display: flex;

      }

      #sidebar {
        width: 70px;
        min-width: 70px;
        height: 100vh;
        z-index: 1000;
        transition: all .25s ease-in-out;
        background-color: #503B31;
        display: flex;
        flex-direction: column;
        position: fixed;
      }

      #sidebar.expand {
        width: 220px;
        min-width: 200px;
      }

      #sidebar.expand+.main-content {
        margin-left: 220px;
      }

      #Allbooks {
        margin-left: 70px;
      }

      #viewBooksContent {
        display: block;
      }

      #viewOrders {
        padding-right: 1.5rem;
      }

      .main-content {
        transition: margin-left 0.3s ease-in-out;
        margin-left: 10px;
        padding: 10px;
        background-color: #F9F7F3;
        min-height: 100vh;
        width: 100%;
        overflow: hidden;
        transition: all 0.35s ease-in-out;
      }

      .toggle-btn {
        background-color: transparent;
        cursor: pointer;
        border: 0;
        padding: 1rem 1.5rem;
      }

      .toggle-btn i {
        font-size: 1.5rem;
        color: #FFF;
      }

      .d-flex {
        display: flex;
        align-items: center;
      }

      #bookifyBTN {
        display: flex;
        align-items: center;
      }

      .sidebar-logo {
        display: flex;
        align-items: center;
        margin-left: 10px;
      }

      .sidebar-logo a {
        margin-left: -80px;
        text-align: left;
        flex-grow: 1;
      }


      #sidebar:not(.expand) .sidebar-logo,
      #sidebar:not(.expand) a.sidebar-link span {
        display: none;
      }

      .sidebar-nav {
        padding: 2rem 0;
        flex: 1 1 auto;
      }

      .sidebar-nav .sidebar-link {
        font-size: 16px;
      }

      a.sidebar-link {
        padding: .625rem 1.625rem;
        color: #fff;
        display: block;
        font-size: 0.9rem;
        white-space: nowrap;
        border-left: 3px solid transparent;
        transition: background-color 0.3s, color 0.3s;
      }

      .sidebar-link i {
        font-size: 1.1rem;
        margin-right: .75rem;
      }

      a.sidebar-link:hover {
        background-color: rgba(255, 255, 255, .075);
        border-left: 3px solid #6e513a;
        text-decoration: none;
        color: #eae6d7;
        width: 95%;
      }

      .sidebar-item {
        position: relative;
      }

      #sidebar:not(.expand) .sidebar-item .sidebar-dropdown {
        position: absolute;
        top: 0;
        left: 70px;
        background-color: #1a4d2e;
        padding: 0;
        min-width: 15rem;
        display: none;
      }

      #sidebar:not(.expand) .sidebar-item:hover .has-dropdown+.sidebar-dropdown {
        display: block;
        max-height: 15em;
        width: 100%;
        opacity: 1;
      }

      #sidebar.expand .sidebar-link[data-bs-toggle="collapse"]::after {
        border: solid;
        border-width: 0 .075rem .075rem 0;
        content: "";
        display: inline-block;
        padding: 2px;
        position: absolute;
        right: 1.5rem;
        top: 1.4rem;
        transform: rotate(-135deg);
        transition: all .2s ease-out;
      }

      #sidebar.expand .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
        transform: rotate(45deg);
        transition: all .2s ease-out;
      }

      .btn {
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        transition: background-color 0.3s;
      }
    </style>

  </head>

  <body>

    <div class="wrapper">
      <aside id="sidebar" class="sidebar">
        <div class="d-flex align-items-left">

          <!-- Sidebar Toggle Button -->
          <button id="bookifyBTN" class="toggle-btn" type="button" aria-label="Toggle Sidebar">
            <img src="./images/Bookify System Logo.webp" alt="Book Icon" width="24" height="24">
          </button>

          <!-- Sidebar Logo, Clicking on this will Show the View Books Section -->
          <div id="bookify" class="sidebar-logo ms-3">
            <a href="javascript:void(0);" onclick="showViewBooks()" class="fs-4 fw-bold text-decoration-none text-light">BOOKIFY</a>
          </div>
        </div>

        <ul class="sidebar-nav">

          <!-- Manage Books with Dropdown -->
          <li class="sidebar-item">
            <a href="#" onclick="showContent('viewBooks', this)" class="sidebar-link">
              <i class="lni lni-book"></i> <!-- Book Icon -->
              <span>Manage Books</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="#" onclick="showContent('viewBooksContent', this)" class="sidebar-link">
              <i class="fas fa-book-open"></i> <!-- View Books Icon -->
              <span>View Books</span>
            </a>
          </li>

          <!-- Manage Categories -->
          <li class="sidebar-item">
            <a href="#" onclick="showContent('viewCategories', this)" class="sidebar-link">
              <i class="fas fa-bell"></i> <!-- Notification Icon -->
              <span>Categories</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a href="#" onclick="showContent('dailySales', this)" class="sidebar-link">
              <i class="fas fa-calendar-day"></i> <!-- Daily Sales Icon -->
              <span>Daily Sales</span>
            </a>
          </li>
          <!-- Monthly Sales with Icon -->
          <li class="sidebar-item">
            <a href="#" onclick="showContent('monthlySales', this)" class="sidebar-link">
              <i class="lni lni-calendar"></i> <!-- Monthly Sales Icon -->
              <span>Monthly Sales</span>
            </a>
          </li>
          <!-- Yearly Sales with Icon -->
          <li class="sidebar-item">
            <a href="#" onclick="showContent('yearlySales', this)" class="sidebar-link">
              <i class="fas fa-calendar-alt"></i> <!-- Yearly Sales Icon -->
              <span>Yearly Sales</span>
            </a>

            <!-- Manage Orders -->
          <li class="sidebar-item">
            <a href="#" onclick="showContent('viewOrders', this)" class="sidebar-link">
              <i class="fas fa-shopping-cart"></i> <!-- Orders Icon -->
              <span>Manage Orders</span>
            </a>
          </li>
        </ul>

        <div class="sidebar-footer">
          <a href="javascript:void(0)" id="logoutBtn" class="sidebar-link">
            <i class="lni lni-exit"></i>
            <span>Logout</span>
          </a>
        </div>
      </aside>

      <div class="main-content" id="Allbooks">
        <div id="viewBooks" class="content-section">
          <div class="topTable">
            <h2>All Books</h2>
            <button type="button" id="buttonAdd" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal" style="background-color: #06D6A0; color: #00654f; font-weight: 700;">
              Add Book
            </button>
            <?php include('add-book.php'); ?>
          </div>

          <div class="table-responsive" style="width: 100%; overflow-x: auto;">
            <table id="booksTable" class="table table-bordered table-striped" style="width: 100%;">
              <thead class="table-dark">
                <tr>
                  <th>ID</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Category</th>
                  <th>Description</th>
                  <th>Image</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="booksList">
                <?php foreach ($books as $book): ?>
                  <tr>
                    <td><?= htmlspecialchars($book['book_id']); ?></td>
                    <td><?= htmlspecialchars($book['title']); ?></td>
                    <td><?= htmlspecialchars($book['author']); ?></td>
                    <td>₱<?= htmlspecialchars($book['price']); ?></td>
                    <td><?= htmlspecialchars($book['stock']); ?></td>
                    <td><?= htmlspecialchars($book['category']); ?></td>
                    <td><?= htmlspecialchars($book['description']); ?></td>
                    <td>
                      <img src="./images/books/<?= htmlspecialchars($book['image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>" style="width: 50px; height: auto;">
                    </td>
                    <td>

                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editBook<?php echo $book['book_id']; ?>">
                        Edit
                      </button>
                      <?php include('edit-book.php'); ?>

                      <a href="admin-dashboard.php?delete_book=<?= $book['book_id']; ?>" class="btn btn-sm btn-danger" style="color: #9c2018; font-weight: 700;">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <style>
          .topTable {
            display: flex;
            justify-content: space-between;
            align-items: center;
            vertical-align: bottom;
            margin-top: 10px;
            margin-bottom: 20px;
          }

          .topTable h2 {
            margin: 0;
          }

          .table-responsive {
            margin-top: 20px;
            margin-bottom: 20px;
          }

          #booksTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
          }

          #booksTable th {
            background-color: #343a40;
            color: white;
            text-align: left;
            padding: 10px 15px;
            font-size: 1rem;
            font-weight: 600;
            border: 1px solid #ddd;
          }

          #booksTable td {
            padding: 8px 15px;
            font-size: 0.9rem;
            border: 1px solid #ddd;
            vertical-align: middle;
          }

          #booksTable tr:hover {
            background-color: #f8f9fa;
          }

          #booksTable td img {
            width: 50px;
            height: auto;
            border-radius: 5px;
          }

          #booksTable .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 10px 20px;
            border-radius: 12px;
            height: 40px;
            max-width: 70px;
          }

          #booksTable .btn-primary {
            background-color: #FFD166;
          }

          #booksTable .btn-danger {
            background-color: #FE938C;
          }

          #booksTable .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
          }

          #booksTable {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
          }

          .content-section {
            padding: 20px;
          }
        </style>

        <!-- Main Content for View Books -->
        <div id="viewBooksContent" class="main-content" style="display: none;">
          <!-- Search and Filter Section -->
          <div class="mb-4">
            <form method="GET" class="d-flex" onsubmit="return redirectToViewBooks();">
              <!-- Search by Book Title -->
              <input type="text" name="search" class="form-control search-input" placeholder="Search by title..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" aria-label="Search">

              <!-- Filter by Stock -->
              <select name="stock_filter" class="form-control stock-filter mx-2">
                <option value="">Filter by stock</option>
                <option value="in_stock" <?= isset($_GET['stock_filter']) && $_GET['stock_filter'] == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                <option value="out_of_stock" <?= isset($_GET['stock_filter']) && $_GET['stock_filter'] == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
              </select>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-search">Search & Filter</button>
            </form>
          </div>

          <!-- Books Grid -->
          <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php
            // Define variables from GET request
            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
            $stockFilter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';

            // Filter the books array based on the search and stock filter
            foreach ($books as $book):
              $titleMatch = stripos($book['title'], $searchTerm) !== false;
              $stockMatch = true;

              // Apply stock filter
              if ($stockFilter == 'in_stock' && $book['stock'] <= 0) {
                $stockMatch = false;
              } elseif ($stockFilter == 'out_of_stock' && $book['stock'] > 0) {
                $stockMatch = false;
              }

              // Display book only if it matches both search and stock filter criteria
              if ($titleMatch && $stockMatch):
            ?>
                <div class="col">
                  <div class="card">
                    <img src="./images/books/<?= htmlspecialchars($book['image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>" class="card-img-top" style="height: 250px; object-fit: fill;">
                    <div class="card-body">
                      <h5 class="card-title text-center "><?= htmlspecialchars($book['title']); ?></h5>
                      <p class="card-text"><strong>Author:</strong> <?= htmlspecialchars($book['author']); ?></p>
                      <p class="card-text"><strong>Price:</strong> <?= htmlspecialchars($book['price']); ?></p>
                      <p class="card-text"><strong>Stock:</strong> <?= htmlspecialchars($book['stock']); ?></p>
                      <p class="card-text"><strong>Category:</strong> <?= htmlspecialchars($book['category']); ?></p>
                      <p class="card-text"><strong>Description:</strong> <?= htmlspecialchars($book['description']); ?></p>
                    </div>
                  </div>
                </div>
            <?php
              endif;
            endforeach;
            ?>
          </div>
        </div>

        <!-- CSS for Styling the Form and Buttons -->
        <style>
          .search-input,
          .stock-filter {
            width: 300px;
            height: 50px;
            border-radius: 25px;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            margin-right: 15px;
          }

          .stock-filter {
            width: 250px;
            text-align: center;
          }

          .btn-search {
            width: 200px;
            height: 50px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            cursor: pointer;
          }

          .btn-search:hover {
            background-color: #0056b3;
          }

          .mb-4 {
            margin-bottom: 30px;
          }
        </style>

      </div>

      <div id="viewCategories" class="content-section" style="display: none;">
        <h2>Manage Categories</h2>

        <!-- Add New Category Form -->
        <form method="POST" action="admin-dashboard.php">
          <div class="mb-3">
            <label for="category_name" class="form-label">Category Name</label>
            <input type="text" name="name" id="category_name" class="form-control">
          </div>
          <div class="mb-3">
            <label for="category_description" class="form-label">Description</label>
            <input type="text" name="description" id="category_description" class="form-control">
          </div>
          <button type="submit" id="saveCat" name="add_category" class="btn btn-success">Add Category</button>
        </form>

        <br> <!-- Add some space before the table -->

        <div class="table-responsive" style="width: 98%; overflow-x: auto;">
          <table id="ordersTable" class="table table-bordered table-striped" style="width: 100%; table-layout: fixed;">
            <thead class="table-dark">
              <tr>
                <th>Category ID</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="categoriesList">
              <?php foreach ($categories as $category): ?>
                <tr>
                  <td><?php echo htmlspecialchars($category->category_id); ?></td>
                  <td><?php echo htmlspecialchars($category->name); ?></td>
                  <td><?php echo htmlspecialchars($category->description); ?></td>
                  <td>
                    <!-- Update Button -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateCategoryModal<?php echo $category->category_id; ?>">
                      Update
                    </button>

                    <?php include('update-category.php') ?>

                    <!-- Delete Button -->
                    <a href="admin-dashboard.php?delete_category=<?= $category->category_id; ?>"
                      class="btn btn-sm btn-danger"
                      onclick="return confirm('Are you sure you want to delete this category?');"
                      title="Delete Category">
                      <i class="fas fa-trash"></i> Delete
                    </a>

                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <style>
        h2 {
          margin-top: 20px;
          font-weight: 500;
        }

        #ordersTable th {
          background-color: #343a40;
          color: white;
        }

        #ordersTable th,
        #ordersTable td {
          padding: 10spx;
          text-align: center;
          vertical-align: middle;
        }

        .table-dark {
          background-color: #140D4F;
          height: 50px;
        }

        .form-control {
          width: 98%;
        }

        #saveCat {
          width: 15%;
          background-color: #F4743B;
          border-radius: 10px;
          border-style: none;
        }

        .btn-primary {
          background-color: #3C6E71;
          border-radius: 10px;
          border-style: none;
        }

        .btn-primary:hover {
          background-color: rgb(49, 97, 99);
        }

        .btn-danger {
          padding-left: 10px;
          border-radius: 10px;
          border-style: none;
        }
      </style>


      <!-- Main Content for Viewing Daily Sales -->
      <div id="dailySales" class="content-section" style="display: none;">
        <h2 class="section-title">Sales Overview</h2>

        <div class="table-responsive" style="width: 100%; overflow-x: auto;">
          <table id="salesTable" class="table table-bordered table-striped" style="width: 98%; table-layout: fixed;">
            <thead class="table-primary">
              <tr>
                <th>Sale ID</th>
                <th>User ID</th>
                <th>Total Price</th>
                <th>Sale Date</th>
              </tr>
            </thead>
            <tbody id="salesList">
              <!-- PHP loop to fetch and display sales records -->
              <?php if (is_array($dailySales) && count($dailySales) > 0): ?>
                <?php
                $totalOrders = count($dailySales);  // Total number of sales
                $totalRevenue = 0;  // Initialize total revenue
                $totalSales = 0;    // Initialize total sales amount

                foreach ($dailySales as $sale):
                  $totalRevenue += $sale['total_amount'];  // Add to total revenue
                  $totalSales++;  // Increment total sales count
                ?>
                  <tr>
                    <td><?= htmlspecialchars($sale['sale_id']); ?></td>
                    <td><?= htmlspecialchars($sale['user_id']); ?></td>
                    <td>₱<?= htmlspecialchars($sale['total_amount']); ?></td>
                    <td><?= htmlspecialchars($sale['sale_date']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4">No sales data for today.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Sales Report Section -->
        <?php if (count($dailySales) > 0): ?>
          <div class="sales-report mt-4">
            <h3 class="report-title">Sales Report</h3>
            <div class="report-container">
              <!-- Total Orders -->
              <div class="report-item individual-report">
                <strong>Total Orders:</strong>
                <p><?= $totalOrders; ?></p>
              </div>

              <!-- Total Sales -->
              <div class="report-item individual-report">
                <strong>Total Sales:</strong>
                <p><?= $totalSales; ?></p>
              </div>

              <!-- Total Revenue -->
              <div class="report-item individual-report">
                <strong>Total Revenue:</strong>
                <p>₱<?= number_format($totalRevenue, 2); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <style>
        /* Main Section Title */
        .section-title {
          text-align: center;
          font-size: 2rem;
          font-weight: bold;
          margin-top: 40px;
          margin-bottom: 30px;
          color: #2c3e50;
        }

        /* Sales Report Title */
        .report-title {
          text-align: center;
          font-size: 2rem;
          font-weight: bold;
          margin-top: 40px;
          margin-bottom: 20px;
          color: #2c3e50;
        }

        /* Table Styling */
        #salesTable {
          margin-bottom: 20px;
          border-spacing: 0;
          border-collapse: collapse;
        }

        #salesTable th,
        #salesTable td {
          padding: 5px;
          text-align: center;
          font-size: 1rem;
        }

        #salesTable th {
          background-color: #3498db;
          color: #fff;
        }

        #salesTable tr:nth-child(even) {
          background-color: #f4f6f7;
        }

        #salesTable tr:hover {
          background-color: #dce8f0;
          cursor: pointer;
        }

        /* Sales Report Container */
        .report-container {
          display: flex;
          justify-content: space-between;
          gap: 1rem;
          background-color: transparent;
          padding: 20px;
          margin-top: 20px;
        }

        /* Individual Report Item */
        .individual-report {
          width: 30%;
          text-align: center;
          padding: 20px;
          background-color: #ecf0f1;
          border-radius: 8px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          transition: background-color 0.3s ease;
        }

        /* Report Item Title Styling */
        .individual-report strong {
          display: block;
          font-size: 1.2rem;
          margin-bottom: 8px;
          color: #2c3e50;
        }

        /* Report Item Value Styling */
        .individual-report p {
          font-size: 1.5rem;
          font-weight: bold;
          color: #2980b9;
        }

        /* Hover Effect for Report Item */
        .individual-report:hover {
          background-color: #bdc3c7;
          cursor: pointer;
        }
      </style>

      <!-- Main Content for Viewing Monthly Sales -->
      <div id="monthlySales" class="content-section" style="display: none;">
        <h2 class="section-title">Monthly Sales Overview</h2>

        <div class="table-responsive" style="width: 100%; overflow-x: auto;">
          <table id="monthlySalesTable" class="table table-bordered table-striped" style="width: 98%; table-layout: fixed;">
            <thead class="table-primary">
              <tr>
                <th>Sale ID</th>
                <th>User ID</th>
                <th>Total Price</th>
                <th>Sale Date</th>
              </tr>
            </thead>
            <tbody id="monthlySalesList">
              <!-- PHP loop to fetch and display sales records -->
              <?php if (is_array($monthlySales) && count($monthlySales) > 0): ?>
                <?php
                $totalMonthlyOrders = count($monthlySales); // Total number of sales
                $totalMonthlyRevenue = 0; // Initialize total revenue
                $totalMonthlySales = 0;   // Initialize total sales amount

                foreach ($monthlySales as $sale):
                  $totalMonthlyRevenue += $sale['total_amount']; // Add to total revenue
                  $totalMonthlySales++; // Increment total sales count
                ?>
                  <tr>
                    <td><?= htmlspecialchars($sale['sale_id']); ?></td>
                    <td><?= htmlspecialchars($sale['user_id']); ?></td>
                    <td>₱<?= htmlspecialchars($sale['total_amount']); ?></td>
                    <td><?= htmlspecialchars($sale['sale_date']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4">No sales data for this month.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Sales Report Section -->
        <?php if (count($monthlySales) > 0): ?>
          <div class="sales-report mt-4">
            <h3 class="report-title">Monthly Sales Report</h3>
            <div class="report-container">
              <!-- Total Orders -->
              <div class="report-item individual-report">
                <strong>Total Orders:</strong>
                <p><?= $totalMonthlyOrders; ?></p>
              </div>

              <!-- Total Sales -->
              <div class="report-item individual-report">
                <strong>Total Sales:</strong>
                <p><?= $totalMonthlySales; ?></p>
              </div>

              <!-- Total Revenue -->
              <div class="report-item individual-report">
                <strong>Total Revenue:</strong>
                <p>₱<?= number_format($totalMonthlyRevenue, 2); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <style>
        /* Reuse the styles from Daily Sales section */
        .section-title {
          text-align: center;
          font-size: 2rem;
          font-weight: bold;
          margin-top: 40px;
          margin-bottom: 30px;
          color: #2c3e50;
        }

        .report-title {
          text-align: center;
          font-size: 2rem;
          font-weight: bold;
          margin-top: 40px;
          margin-bottom: 20px;
          color: #2c3e50;
        }

        #monthlySalesTable {
          margin-bottom: 20px;
          border-spacing: 0;
          border-collapse: collapse;
        }

        #monthlySalesTable th,
        #monthlySalesTable td {
          padding: 5px;
          text-align: center;
          font-size: 1rem;
        }

        #monthlySalesTable th {
          background-color: #3498db;
          color: #fff;
        }

        #monthlySalesTable tr:nth-child(even) {
          background-color: #f4f6f7;
        }

        #monthlySalesTable tr:hover {
          background-color: #dce8f0;
          cursor: pointer;
        }

        .report-container {
          display: flex;
          justify-content: space-between;
          gap: 1rem;
          background-color: transparent;
          padding: 20px;
          margin-top: 20px;
        }

        .individual-report {
          width: 30%;
          text-align: center;
          padding: 20px;
          background-color: #ecf0f1;
          border-radius: 8px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          transition: background-color 0.3s ease;
        }

        .individual-report strong {
          display: block;
          font-size: 1.2rem;
          margin-bottom: 8px;
          color: #2c3e50;
        }

        .individual-report p {
          font-size: 1.5rem;
          font-weight: bold;
          color: #2980b9;
        }

        .individual-report:hover {
          background-color: #bdc3c7;
          cursor: pointer;
        }
      </style>

      <!-- Main Content for Viewing Yearly Sales -->
      <div id="yearlySales" class="content-section" style="display: none;">
        <h2 class="section-title">Yearly Sales Overview</h2>

        <div class="table-responsive" style="width: 100%; overflow-x: auto;">
          <table id="yearlySalesTable" class="table table-bordered table-striped" style="width: 98%; table-layout: fixed;">
            <thead class="table-primary">
              <tr>
                <th>Sale ID</th>
                <th>User ID</th>
                <th>Total Price</th>
                <th>Sale Date</th>
              </tr>
            </thead>
            <tbody id="yearlySalesList">
              <!-- PHP loop to fetch and display sales records -->
              <?php if (is_array($yearlySales) && count($yearlySales) > 0): ?>
                <?php
                $totalYearlyOrders = count($yearlySales); // Total number of yearly sales
                $totalYearlyRevenue = 0; // Initialize total revenue
                $totalYearlySales = 0;   // Initialize total sales count

                foreach ($yearlySales as $sale):
                  $totalYearlyRevenue += $sale['total_amount']; // Add to total revenue
                  $totalYearlySales++; // Increment total sales count
                ?>
                  <tr>
                    <td><?= htmlspecialchars($sale['sale_id']); ?></td>
                    <td><?= htmlspecialchars($sale['user_id']); ?></td>
                    <td>₱<?= htmlspecialchars($sale['total_amount']); ?></td>
                    <td><?= htmlspecialchars($sale['sale_date']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4">No sales data for this year.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Sales Report Section -->
        <?php if (count($yearlySales) > 0): ?>
          <div class="sales-report mt-4">
            <h3 class="report-title">Yearly Sales Report</h3>
            <div class="report-container">
              <!-- Total Orders -->
              <div class="report-item individual-report">
                <strong>Total Orders:</strong>
                <p><?= $totalYearlyOrders; ?></p>
              </div>

              <!-- Total Sales -->
              <div class="report-item individual-report">
                <strong>Total Sales:</strong>
                <p><?= $totalYearlySales; ?></p>
              </div>

              <!-- Total Revenue -->
              <div class="report-item individual-report">
                <strong>Total Revenue:</strong>
                <p>₱<?= number_format($totalYearlyRevenue, 2); ?></p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <style>
        /* Section Title */
        .section-title {
          text-align: center;
          font-size: 2rem;
          font-weight: bold;
          margin-top: 40px;
          margin-bottom: 30px;
          color: #2c3e50;
        }

        /* Report Title */
        .report-title {
          text-align: center;
          font-size: 2rem;
          font-weight: bold;
          margin-top: 40px;
          margin-bottom: 20px;
          color: #2c3e50;
        }

        /* Yearly Sales Table */
        #yearlySalesTable {
          margin-bottom: 20px;
          border-spacing: 0;
          border-collapse: collapse;
        }

        #yearlySalesTable th,
        #yearlySalesTable td {
          padding: 5px;
          text-align: center;
          font-size: 1rem;
        }

        #yearlySalesTable th {
          background-color: #3498db;
          color: #fff;
        }

        #yearlySalesTable tr:nth-child(even) {
          background-color: #f4f6f7;
        }

        #yearlySalesTable tr:hover {
          background-color: #dce8f0;
          cursor: pointer;
        }

        /* Sales Report Container */
        .report-container {
          display: flex;
          justify-content: space-between;
          gap: 1rem;
          background-color: transparent;
          padding: 20px;
          margin-top: 20px;
        }

        /* Individual Report Item */
        .individual-report {
          width: 30%;
          text-align: center;
          padding: 20px;
          background-color: #ecf0f1;
          border-radius: 8px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          transition: background-color 0.3s ease;
        }

        /* Report Item Title */
        .individual-report strong {
          display: block;
          font-size: 1.2rem;
          margin-bottom: 8px;
          color: #2c3e50;
        }

        /* Report Item Value */
        .individual-report p {
          font-size: 1.5rem;
          font-weight: bold;
          color: #2980b9;
        }

        /* Hover Effect */
        .individual-report:hover {
          background-color: #bdc3c7;
          cursor: pointer;
        }
      </style>

      <!-- Main Content for Manage Orders -->
      <div id="viewOrders" class="content-section" style="display: none;">
        <h2>Manage Orders</h2>
        <div class="table-responsive" style="width: 100%; overflow-x: auto;">
          <table id="ordersTable" class="table table-bordered table-striped" style="width: 100%; table-layout: fixed;">
            <thead class="table-dark">
              <tr>
                <th>Order ID</th>
                <th>User ID</th>
                <th>Book ID</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="ordersList">
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><?= htmlspecialchars($order['order_id']); ?></td>
                  <td><?= htmlspecialchars($order['customer_name']); ?></td>
                  <td><?= htmlspecialchars($order['book_title']); ?></td>
                  <td><?= htmlspecialchars($order['quantity']); ?></td>
                  <td>₱<?= number_format($order['price'], 2); ?></td>
                  <td><?= htmlspecialchars($order['order_date']); ?></td>
                  <td><?= htmlspecialchars($order['status']); ?></td>
                  <td>
                    <!-- View Button -->
                    <button title="View Order" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#view-orders-modal">
                      <i class="fas fa-eye"></i>
                      View
                    </button>

                    <?php include_once("view-order.php") ?>

                    <!-- Delete Button -->
                    <a href="delete-order.php?id=<?= $order['order_id']; ?>"
                      class="btn btn-sm btn-danger"
                      onclick="return confirm('Are you sure you want to delete this order?');"
                      title="Delete Order">
                      <i class="fas fa-trash"></i> Delete
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
      crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  </body>

  </html>

  <script>
    function showViewBooks() {
      // Get the View Books section
      var viewBooksSection = document.getElementById('viewBooks');

      // Toggle visibility of the View Books section
      if (viewBooksSection.style.display === 'none' || viewBooksSection.style.display === '') {
        viewBooksSection.style.display = 'block'; // Show the content
      } else {
        viewBooksSection.style.display = 'none'; // Hide the content
      }

      document.getElementById('viewBooksContent').style.display = 'none';
    }
  </script>


  <script>
    // Function to toggle content visibility
    function showContent(contentId, link) {
      const allContents = document.querySelectorAll('.main-content > div');
      allContents.forEach(content => content.style.display = 'none'); // Hide all content

      const content = document.getElementById(contentId);
      content.style.display = 'block'; // Show the selected content

      if (contentId === 'viewBooksContent') {
        fetchBooks(); // Fetch books when the 'View Books' content is shown
      }
    }

    // Toggle dropdown visibility
    function toggleDropdown(dropdownId, link) {
      const dropdown = document.getElementById(dropdownId);
      dropdown.classList.toggle('collapse');
      const expanded = link.getAttribute('aria-expanded') === 'true' ? 'false' : 'true';
      link.setAttribute('aria-expanded', expanded);
    }
  </script>

  <script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
      document.querySelector("#sidebar").classList.toggle("expand");
    });
  </script>

  <script>
    // Check if the success message is set for Admin or Collector
    <?php if (isset($_SESSION['success_message'])): ?>
      Swal.fire({
        title: 'Success!',
        text: '<?php echo $_SESSION['success_message']; ?>',
        icon: 'success',
        confirmButtonText: 'OK',
        showConfirmButton: true,
        position: 'center', // Ensures it's centered on the screen
        willClose: () => {
          // Optional: Redirect user after clicking OK (can be done after success)
          // window.location.href = "admin_dashboard.php"; // Example redirect
        }
      });
      <?php unset($_SESSION['success_message']); ?> // Clear the success message after showing the alert
    <?php elseif (isset($_SESSION['error_message'])): ?>
      Swal.fire({
        title: 'Error!',
        text: '<?php echo $_SESSION['error_message']; ?>',
        icon: 'error',
        confirmButtonText: 'OK',
        showConfirmButton: true,
        position: 'center',
        willClose: () => {
          // Optional: Handle action after error
        }
      });
      <?php unset($_SESSION['error_message']); ?> // Clear the error message after showing the alert
    <?php endif; ?>
  </script>

  <script>
    // Toggle dropdown visibility
    function toggleDropdown(dropdownId, link) {
      var dropdown = document.getElementById(dropdownId);
      var isCollapsed = dropdown.classList.contains('collapse');

      // Collapse all dropdowns before opening the new one
      var allDropdowns = document.querySelectorAll('.sidebar-dropdown');
      allDropdowns.forEach(function(d) {
        d.classList.add('collapse');
      });

      // Toggle current dropdown
      if (isCollapsed) {
        dropdown.classList.remove('collapse');
      } else {
        dropdown.classList.add('collapse');
      }
    }
  </script>

  <script>
    // Function to show and hide content based on clicked sidebar button
    function showContent(section, element) {
      // Hide all sections
      var sections = document.querySelectorAll('.content-section');
      sections.forEach(function(section) {
        section.style.display = 'none';
      });

      // Highlight the active sidebar link
      var links = document.querySelectorAll('.nav a');
      links.forEach(function(link) {
        link.classList.remove('active');
      });
      element.classList.add('active');

      // Show the selected section
      var selectedSection = document.getElementById(section);
      if (selectedSection) {
        selectedSection.style.display = 'block';
      }
    }

    // Function to handle logout confirmation (replace with actual logout functionality)
    function confirmLogout() {
      var logoutConfirmed = confirm("Are you sure you want to logout?");
      if (logoutConfirmed) {
        // Handle actual logout (e.g., redirect to a logout page or clear session)
        window.location.href = "logout.php"; // Replace with actual logout URL
      }
    }
  </script>

  <script>
    // Add an event listener to the logout button
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
      e.preventDefault(); // Prevent the default action of the link (to not navigate immediately)

      // Show SweetAlert confirmation dialog
      Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out.",
        icon: 'warning',
        showCancelButton: true, // Show cancel button
        confirmButtonColor: '#d33', // Confirm button color
        cancelButtonColor: '#3085d6', // Cancel button color
        confirmButtonText: 'Yes, log me out!', // Text for confirm button
        cancelButtonText: 'Cancel' // Text for cancel button
      }).then((result) => {
        // If the user confirms the logout action
        if (result.isConfirmed) {
          // Redirect to the logout page
          window.location.href = 'logout.php'; // Adjust the logout URL accordingly
        }
      });
    });
  </script>