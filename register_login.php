<?php

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include "db.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$secret_key = "eaf21a16908fbdd7c9c33aa9938213ec0bf39e262036a6856660b4c235438e68";
$issuer_claim = "localhost"; // Şirket test ederken burası değişebilir
$audience_claim = "localhost";
$issuedat_claim = time();
$notbefore_claim = $issuedat_claim;
$expire_claim = $issuedat_claim + (60 * 60); // 1 saat

$method = $_SERVER["REQUEST_METHOD"];
$input = json_decode(file_get_contents("php://input"), true);

$action = $_GET['action'] ?? null;

// Register
if ($action === 'register' && $method === "POST") {

    if (!isset($input["username"]) || !isset($input["password"]) || empty(trim($input['username'])) || empty(trim($input['password']))) {
        http_response_code(400);
        echo json_encode(["message" => "Username and or password missing."]);
        exit;
    }

    $username_db = $input["username"];
    $unhashed_password = $input["password"];

    $stmt_check = $connection->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->bind_param("s", $username_db);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(["message" => "This username already exists."]);
        exit;
    }

    $hashed_password = password_hash($unhashed_password, PASSWORD_DEFAULT);

    $stmt_insert = $connection->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $username_db, $hashed_password);

    $username_db = $input["username"];

    if ($stmt_insert->execute()) {
        http_response_code(201);
        echo json_encode(["Message" => "User has been registered successfully. "]);
    } else {
        http_response_code(500);
        echo json_encode(["Message" => "User registration failed." . $stmt_insert->error]);
    }
    $stmt_insert->close();
}

// Login
elseif ($action === "login" && $method === "POST") {
    if (!isset($input['username']) || !isset($input['password']) || empty(trim($input['username'])) || empty(trim($input['password']))) {
        http_response_code(400);
        echo json_encode(["message" => "Username and password are required for login."]);
        exit;
    }

    $stmt = $connection->prepare("SELECT id, username, password FROM users WHERE username = ?;");
    $stmt->bind_param("s", $username_db);

    $username_db = $input["username"];
    $submitted_password = $input["password"];
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        if (password_verify($submitted_password, $user_data['password'])) {
            $token = array(
                "iss" => $issuer_claim,
                "aud" => $audience_claim,
                "iat" => $issuedat_claim,
                "nbf" => $notbefore_claim,
                "exp" => $expire_claim,
                "data" => array(
                    "id" => $user_data['id'],
                    "username" => $user_data['username']
                )
            );

            http_response_code(200);
            $jwt = JWT::encode($token, $secret_key, 'HS256');
            echo json_encode(
                array(
                    "message" => "Login successful.",
                    "token" => $jwt,
                    "expiresIn" => $expire_claim
                )
            );
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Login failed. Invalid credentials."]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Login failed. Invalid credentials."]);
    }
    $stmt->close();
} else {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found or method not allowed."]);
}

$connection->close();
?>