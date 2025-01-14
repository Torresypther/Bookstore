<?php 
require_once('connection.php');

if (isset($_POST['update_category'])) {
    $category_id = $_POST['id'];
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];

    // Update the category in the database
    $stmt = $pdo->prepare("UPDATE categories SET name = :category_name, description = :description WHERE category_id = :category_id");
    $stmt->execute([
        'category_name' => $category_name,
        'description' => $description,
        'category_id' => $category_id
    ]);

    // Set a success message and reload the page to show the updated data
    $_SESSION['message'] = 'Category updated successfully!';
    header('Location: admin-dashboard.php#viewCategories');
    exit();
}

// Fetch categories from the database
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="modal fade" id="updateCategoryModal<?php echo $category->category_id; ?>" tabindex="-1" aria-labelledby="updateCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="updateCategoryModalLabel">Update Category</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <form method="POST" action="update-category.php">
                        <!-- Hidden input to store category ID -->
                        <input type="hidden" name="id" value="<?php echo $category->category_id; ?>">

                        <!-- Input field for category name -->
                        <div class="mb-3">
                            <label for="modal_category_name" class="form-label">Category Name</label>
                            <input type="text" name="category_name" id="modal_category_name" class="form-control"
                                value="<?php echo htmlspecialchars($category->name); ?>" required>
                        </div>

                        <!-- Input field for category description -->
                        <div class="mb-3">
                            <label for="modal_description" class="form-label">Description</label>
                            <input type="text" name="description" id="modal_description" class="form-control"
                                value="<?php echo htmlspecialchars($category->description); ?>" required>
                        </div>

                        <!-- Submit button for the form -->
                        <button type="submit" name="update_category" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
