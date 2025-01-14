<?php
include_once('connection.php');
session_start();

// Initialize a flag to track success or failure
$registration_success = false;
$error_message = "";

// Handle user registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Insert into users table (or your user table)
    try {
        $query = "INSERT INTO users (name, email, password) VALUES (:username, :email, :password)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->execute();

        // Set success flag
        $registration_success = true;
    } catch (PDOException $e) {
        $error_message = $e->getMessage();
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['email_or_username'];
    $password = $_POST['password']; // The raw password entered by the user

    // First, check in the admin table
    $checkAdminQuery = "SELECT * FROM admin WHERE name = :username OR email = :email";
    $stmt = $pdo->prepare($checkAdminQuery);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    $stmt->execute();
    $adminResult = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminResult) {
        // If found in admin table, check password
        if ($password === $adminResult['password']) {
            $_SESSION['user_id'] = $adminResult['admin_id'];
            $_SESSION['username'] = $adminResult['name'];
            $_SESSION['role'] = 'admin';
            header("Location: admin-dashboard.php"); // Redirect to admin dashboard
            exit();
        } else {
            $error_message = "Invalid password!";
        }
    } else {
        // If not found in admin table, check in users table
        $checkResidentQuery = "SELECT * FROM users WHERE name = :username OR email = :email";
        $stmt = $pdo->prepare($checkResidentQuery);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $username, PDO::PARAM_STR);
        $stmt->execute();
        $residentResult = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($residentResult) {
            // If found in users table, check password
            if ($password === $residentResult['password']) {
                $_SESSION['user_id'] = $residentResult['user_id'];
                $_SESSION['name'] = $residentResult['name'];
                $_SESSION['role'] = 'user';
                header("Location: user-homepage.php#home"); // Redirect to user dashboard
                exit();
            } else {
                $error_message = "Invalid password!";
            }
        } else {
            $error_message = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookify SignUp & LogIn</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<style>
  @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

  * {
    box-sizing: border-box;
  }

  body {
    background: #f6f5f7;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    font-family: 'Montserrat', sans-serif;
    height: 100vh;
    margin: -20px 0 50px;
  }

  h1 {
    font-weight: bold;
    margin: 0;
    color: black;
  }

  h2 {
    text-align: center;
  }

  p {
    font-size: 14px;
    font-weight: 100;
    line-height: 20px;
    letter-spacing: 0.5px;
    margin: 20px 0 30px;
    color: white;

  }

  span {
    font-size: 12px;
  }

  a {
    color: #333;
    font-size: 14px;
    text-decoration: none;
    margin: 15px 0;
  }

  button {
    border-radius: 20px;
    border: 1px solid #FF4B2B;
    background-color: #FF4B2B;
    color: #FFFFFF;
    font-size: 12px;
    font-weight: bold;
    padding: 12px 45px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: transform 80ms ease-in;
  }

  button:active {
    transform: scale(0.95);
  }

  button:focus {
    outline: none;
  }

  button.ghost {
    color: #fff;
  }

  form {
    background-color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 0 50px;
    height: 100%;
    text-align: center;
  }

  input {
    background-color: #eee;
    border: none;
    padding: 12px 15px;
    margin: 8px 0;
    width: 100%;
  }

  .container {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25),
      0 10px 10px rgba(0, 0, 0, 0.22);
    position: relative;
    overflow: hidden;
    width: 768px;
    max-width: 100%;
    min-height: 480px;
  }

  .form-container {
    position: absolute;
    top: 0;
    height: 100%;
    transition: all 0.6s ease-in-out;
  }

  .sign-in-container {
    left: 0;
    width: 50%;
    z-index: 2;
  }

  .container.right-panel-active .sign-in-container {
    transform: translateX(100%);
  }

  .sign-up-container {
    left: 0;
    width: 50%;
    opacity: 0;
    z-index: 1;
  }

  .container.right-panel-active .sign-up-container {
    transform: translateX(100%);
    opacity: 1;
    z-index: 5;
    animation: show 0.6s;
  }

  @keyframes show {

    0%,
    49.99% {
      opacity: 0;
      z-index: 1;
    }

    50%,
    100% {
      opacity: 1;
      z-index: 5;
    }
  }

  .overlay-container {
    position: absolute;
    top: 0;
    left: 50%;
    width: 50%;
    height: 100%;
    overflow: hidden;
    transition: transform 0.6s ease-in-out;
    z-index: 100;
  }

  .container.right-panel-active .overlay-container {
    transform: translateX(-100%);
  }

  .overlay {
    background-position: 0 0;
    color: #FFFFFF;
    position: relative;
    left: -100%;
    height: 100%;
    width: 200%;
    transform: translateX(0);
    transition: transform 0.6s ease-in-out;
  }

  .container.right-panel-active .overlay {
    transform: translateX(50%);
  }

  .overlay-panel {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    padding: 0 40px;
    text-align: center;
    top: 0;
    height: 100%;
    width: 50%;
    transform: translateX(0);
    transition: transform 0.6s ease-in-out;
    background: url(./images/books.jpg);
    background-repeat: no-repeat;
    background-size: cover;
  }

  .overlay-panel::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.15);
    z-index: 1;
  }

  .overlay-left {
    transform: translateX(-20%);
  }

  .container.right-panel-active .overlay-left {
    transform: translateX(0);
  }

  .overlay-right {
    right: 0;
    transform: translateX(0);
  }

  .container.right-panel-active .overlay-right {
    transform: translateX(20%);
  }

  .social-container {
    margin: 20px 0;
  }

  .social-container a {
    border: 1px solid #DDDDDD;
    border-radius: 50%;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    margin: 0 5px;
    height: 40px;
    width: 40px;
  }

  .social {
    text-decoration: none;
    margin: 0 10px;
    font-size: 24px;
  }

  /* Facebook Styles */
  .social.facebook {
    color: #3b5998;
    /* Default Facebook Blue */
    background-color: transparent;
    border: 2px solid #3b5998;
  }

  .social.facebook:hover {
    background-color: #3b5998;
    /* Blue Background */
    color: #ffffff;
    /* White Icon */
  }

  /* Google Styles */
  .social.google {
    color: #db4437;
    /* Default Google Red */
    background-color: transparent;
    border: 2px solid #db4437;
  }

  .social.google:hover {
    background-color: #db4437;
    /* Red Background */
    color: #ffffff;
    /* White Icon */
  }

  .social:hover {
    opacity: 0.8;
  }

  footer {
    background-color: #222;
    color: #fff;
    font-size: 14px;
    bottom: 0;
    position: fixed;
    left: 0;
    right: 0;
    text-align: center;
    z-index: 999;
  }

  footer p {
    margin: 10px 0;
  }

  footer i {
    color: red;
  }

  footer a {
    color: #3c97bf;
    text-decoration: none;
  }
</style>  

<body>
    <div class="container" id="container">
        <!-- Registration Form -->
        <div class="form-container sign-up-container">
            <form action="" method="POST">
                <h1>Create Account</h1>
                <div class="social-container">
                    <a href="#" class="social facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social google"><i class="fab fa-google-plus-g"></i></a>
                </div>
                <span>or use your email for registration</span>
                <input type="text" name="name" placeholder="Name" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit" name="register">Sign Up</button>
            </form>
        </div>

        <!-- Login Form -->
        <div class="form-container sign-in-container">
            <form action="" method="POST">
                <h1>Sign in</h1>
                <div class="social-container">
                    <a href="#" class="social facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social google"><i class="fab fa-google-plus-g"></i></a>
                </div>
                <span>or use your account</span>
                <input type="text" name="email_or_username" placeholder="Email or Username" required />
                <input type="password" name="password" placeholder="Password" required />
                <a href="#">Forgot your password?</a>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>Sign in to explore and purchase your favorite books, track your reading list, and enjoy a personalized library experience.</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Create an account to browse a vast collection of books, buy what you love, and dive into the world of reading today.</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
</body>


<script>
  const signUpButton = document.getElementById('signUp');
  const signInButton = document.getElementById('signIn');
  const container = document.getElementById('container');

  signUpButton.addEventListener('click', () => {
    container.classList.add("right-panel-active");
  });

  signInButton.addEventListener('click', () => {
    container.classList.remove("right-panel-active");
  });

  
</script>

       <?php if ($registration_success): ?>
            echo "<script>
              document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                  icon: 'success',
                  title: 'Success!',
                  text: 'User registered successfully.',
                  confirmButtonText: 'OK'
                }).then((result) => {
                  if (result.isConfirmed) {
                    window.location.href = 'signup.php';
                  }
                });
              });
            </script>";
        <?php endif; ?>


</html>