<?php
require_once('connection.php');

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!-- Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Book</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="addBookForm" name="addBookForm">
          <div class="row">
            <div class="col-12 col-md-6 mb-3">
              <label for="title" class="form-label">Title</label>
              <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="col-12 col-md-6 mb-3">
              <label for="author" class="form-label">Author</label>
              <input type="text" class="form-control" id="author" name="author" required>
            </div>
          </div>
          <div class="row">
            <div class="col-12 col-md-6 mb-3">
              <label for="price" class="form-label">Price</label>
              <input type="number" class="form-control" id="price" name="price" required>
            </div>
            <div class="col-12 col-md-6 mb-3">
              <label for="stock" class="form-label">Stock</label>
              <input type="number" class="form-control" id="stock" name="stock" required>
            </div>
          </div>
          <div class="row">
            <div class="col-12 col-md-6 mb-3">
              <label for="category" class="form-label">Category</label>
              <select class="form-select" id="category" name="category" required>
                <?php
                if (!empty($categories)) {
                  foreach ($categories as $category) {
                    echo '<option value="' . $category->category_id . '">' . $category->name . '</option>';
                  }
                }
                ?>
              </select>
            </div>
            <div class="col-12 col-md-6 mb-3">
              <label for="image" class="form-label">Image</label>
              <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="addBook" class="btn btn-primary" form="addBookForm">Add Book</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  #addBookModal .modal-dialog {
    max-width: 60%;
  }

  #addBookModal .modal-body {
    padding: 20px;
  }

  #addBookModal .row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
  }

  #addBookModal .col-12 {
    flex: 1 1 100%;
  }

  #addBookModal .col-md-6 {
    flex: 1 1 48%;
  }

  #addBookModal .form-control,
  #addBookModal .form-select {
    width: 100%;
  }

  @media (max-width: 768px) {
    #addBookModal .col-md-6 {
      flex: 1 1 100%;
    }
  }
</style>