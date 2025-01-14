<?php
require_once('connection.php');

$bookId = $book['book_id'];
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :book_id");
$stmt->execute(['book_id' => $bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!-- Modal -->
<div class="modal fade" id="editBook<?php echo $book['book_id']; ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="editBookLabel">Add New Book</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addBookForm" name="addBookForm" action="edit-book.php" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($book['price']); ?>" min="0" step="0.01">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($book['stock']); ?>" min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select id="category" class="form-select" name="category_id">
                                <option value="" disabled>Select Category</option>
                                <?php
                                $selectedCategoryId = isset($book['category_id']) ? $book['category_id'] : '';
                                if (!empty($categories)) {
                                    foreach ($categories as $category) {
                                        $isSelected = ($category->category_id == $selectedCategoryId) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($category->category_id) . '" ' . $isSelected . '>' . htmlspecialchars($category->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($book['description']); ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_book" id="update_book" class="btn btn-primary" form="addBookForm">Save</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<style>
    #editBook .modal-dialog {
        max-width: 90%;
        width: 90%;
    }

    #editBook .modal-body {
        padding: 20px;
        /* Adjust padding if needed */
    }

    #editBook .row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    #editBook .col-12 {
        flex: 1 1 100%;
    }

    #editBook .col-md-6 {
        flex: 1 1 48%;
    }

    #editBook .form-control,
    #editBook .form-select {
        width: 100%;
    }

    @media (max-width: 768px) {
        #editBook .col-md-6 {
            flex: 1 1 100%;
            /* Stack elements on smaller screens */
        }
    }
</style>