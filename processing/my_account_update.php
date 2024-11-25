<?php
session_start();
require "../public/connect_sql.php";

// Validate user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Sanitize inputs
$id_user = (int)$_SESSION['id'];
$name_user = htmlspecialchars(trim($_POST['name_user']));
$email_user = filter_var($_POST['email_user'], FILTER_SANITIZE_EMAIL);
$phone_number_user = htmlspecialchars(trim($_POST['phone_number_user']));

// Validate email format
if (!filter_var($email_user, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['alert'] = "3"; // Invalid email format
    header("Location: ../my_account.php");
    exit();
}

// Check for duplicate email
$stmt = mysqli_prepare($connection, "SELECT id_user FROM `user` WHERE `email_user` = ? AND `id_user` != ?");
mysqli_stmt_bind_param($stmt, "si", $email_user, $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $_SESSION['alert'] = "3"; // Duplicate email
    header("Location: ../my_account.php");
    exit();
}

// Validate phone number
function isValidPhone(string $phone): bool {
    return preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/", $phone);
}

if (!isValidPhone($phone_number_user)) {
    $_SESSION['alert'] = '2'; // Invalid phone
    header("Location: ../my_account.php");
    exit();
}

// Handle image upload
$fileImageName = $_SESSION['image'] ?? 'default.jpg';
if (isset($_FILES['image_user']) && $_FILES['image_user']['error'] === UPLOAD_ERR_OK) {
    $image = $_FILES['image_user'];
    $target_dir = "../public/images/upload/";
    
    // Validate image type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $imageFileType = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    
    if (in_array($imageFileType, $allowed_types)) {
        $randomValue = time();
        $fileImageName = $name_user . $randomValue . '.' . $imageFileType;
        $target_file = $target_dir . $fileImageName;
        
        if (move_uploaded_file($image["tmp_name"], $target_file)) {
            $_SESSION['image'] = $fileImageName;
        }
    }
}

// Update user information
$stmt = mysqli_prepare($connection, 
    "UPDATE `user` SET 
        `name_user` = ?,
        `email_user` = ?,
        `phone_number_user` = ?,
        `image_user` = ?
    WHERE `id_user` = ?"
);

mysqli_stmt_bind_param($stmt, "ssssi", 
    $name_user,
    $email_user, 
    $phone_number_user,
    $fileImageName,
    $id_user
);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['name'] = $name_user;
    $_SESSION['alert'] = '1'; // Success
} else {
    $_SESSION['alert'] = '0'; // Failed
}

mysqli_stmt_close($stmt);
header("Location: ../my_account.php");
exit();
