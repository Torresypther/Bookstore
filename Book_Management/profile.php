<?php
include_once('connection.php');
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-up.php');  // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch the current user data from the database
    $query = "SELECT * FROM users WHERE user_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $user_id]);

    // Fetch data
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if any data was returned
    if (!$row) {
        echo "No data found.";
        exit;  // Stop further processing if no user data
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Handle form submission for updating user info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = htmlspecialchars($_POST['username']);
    $newEmail = htmlspecialchars($_POST['email']);
    $newPassword = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    // Default to current profile image if no new file is uploaded
    $updatedProfileImage = $row['profile'];

    // Handle file upload if a new image is selected
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profileImage']['tmp_name'];
        $fileName = $_FILES['profileImage']['name'];
        $uploadFileDir = './uploads/profiles/';

        // Ensure the upload directory exists
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        $dest_path = $uploadFileDir . basename($fileName);

        // Move the file to the upload directory
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $updatedProfileImage = $dest_path;
        } else {
            echo "Error uploading file.";
        }
    }

    // Prepare the SQL query to update user information
    $sql = "UPDATE users SET name = ?, email = ?, profile = ? ";
    $params = [$newUsername, $newEmail, $updatedProfileImage];

    // Add password update if a new password is provided
    if ($newPassword) {
        $sql .= ", password = ?";
        $params[] = $newPassword;
    }

    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;

    // Execute the update query
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Success message and session update
        $_SESSION['profile_image'] = $updatedProfileImage;
        $_SESSION['success'] = "Profile updated successfully!";
        header('Location: profile.php');
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            color: #2d3436;
            font-family: 'Arial', sans-serif;
        }

        .form-container {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
            margin-top: 5rem;
        }

        .form-container h2 {
            color: #2d3436;
        }

        .btn-primary {
            background: #6c5ce7;
            border: none;
        }

        .btn-primary:hover {
            background: #341f97;
        }

        .btn-secondary {
            background: #b2bec3;
            border: none;
        }

        .btn-secondary:hover {
            background: #636e72;
        }

        .profile-pic {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4 text-center">Update Profile</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Back Button -->
            <a href="user-homepage.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($row['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($row['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password (leave blank to keep current password)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="mb-3">
                    <label for="profileImage" class="form-label">Profile Picture</label>
                    <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*">
                  <img src="<?= $_SESSION['profile_image'] ?? htmlspecialchars($row['profile']); ?>" alt="Profile Picture" class="mt-3" style="width: 100px; height: 100px; object-fit: cover;">

                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>

        </div>
    </div>

</body>

</html>