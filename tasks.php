<?php

include "db.php";

header("Content-Type: application/json");

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key; 

$secret_key = "eaf21a16908fbdd7c9c33aa9938213ec0bf39e262036a6856660b4c235438e68";
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

$arr = explode(" ", $authHeader);

$jwt = $arr[1] ?? null; // Token

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied. No token provided."]);
    exit;
}

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $loggedInUserID = $decoded->data->id; // User ID from the token's data payload
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(array(
        "message" => "Access denied. Invalid token.",
        "error" => $e->getMessage()
    ));
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];
$input = json_decode(file_get_contents("php://input"), true);

switch($method) {
    case "GET":
        try {
            $baseSql = "SELECT * FROM tasks WHERE user_id = ? AND is_deleted = 0";
            $params = [$loggedInUserID];
            $types = "i";
    
            // Status filter
            if (isset($_GET['status']) && !empty(trim($_GET['status']))) {
                $statusFilter = trim($_GET['status']);
                if (!in_array($statusFilter, ['pending', 'in_progress', 'done'])) {
                     http_response_code(400);
                     echo json_encode(["message" => "Invalid status value."]);
                     exit;
                }
                $baseSql .= " AND status = ?";
                $params[] = $statusFilter;
                $types .= "s";
            }
    
            // Date filter
            if (isset($_GET['date']) && !empty(trim($_GET['date']))) {
                $dateFilter = trim($_GET['date']);
                 if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateFilter)) {
                     http_response_code(400);
                     echo json_encode(["message" => "Invalid date format. Use YYYY-MM-DD."]);
                     exit;
                }
                $baseSql .= " AND due_date = ?";
                $params[] = $dateFilter;
                $types .= "s";
            }
    
            // Tek bir task döndürme
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                // Authenticate
                $taskId = (int)$_GET['id'];
                $owner_id_stmt = $connection->prepare("SELECT user_id FROM tasks WHERE id = ?");
                if ($owner_id_stmt === false) {
                    http_response_code(500);
                    echo json_encode(["message" => "Database error (GET auth prepare failed): " . $connection->error]);
                    exit;
                }
                $owner_id_stmt->bind_param("i", $taskId);
                $owner_id_stmt->execute();

                $result = $owner_id_stmt->get_result();
                $data = $result->fetch_assoc();
                $owner_id = $data["user_id"];

                if ($owner_id != $loggedInUserID) {
                    http_response_code(401);
                    echo json_encode(["message" => "You are not authorized for this action."]);
                    exit;
                }
                $baseSql = "SELECT * FROM tasks WHERE user_id = ? AND id = ? AND is_deleted = 0";
                $params = [$loggedInUserID, $taskId];
                $types = "ii";
            }
    
            $stmt = $connection->prepare($baseSql);
            if ($stmt === false) {
                http_response_code(500);
                echo json_encode(["message" => "Database error (GET prepare failed): " . $connection->error]);
                exit;
            }
    
            // SQL parametrelerini birleştir
            if (!empty($params)) { 
                $stmt->bind_param($types, ...$params);
            }
    
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if (isset($_GET['id']) && is_numeric($_GET['id'])) { // Tek task
                    if ($result->num_rows === 1) {
                        $task = $result->fetch_assoc();
                        echo json_encode($task);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Task not found."]);
                    }
                } else { // Birden çok task (filtrelenmiş olabilir)
                    $tasks = [];
                    while ($row = $result->fetch_assoc()) {
                        $tasks[] = $row;
                    }
                    echo json_encode($tasks);
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to fetch tasks. Error: " . $stmt->error]);
            }
            $stmt->close();
    
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Database error fetching tasks.",
                "error_details" => $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "An unexpected error occurred.",
                "error" => $e->getMessage()
            ]);
        }
        break;

    case "POST":
        $stmt_insert = $connection->prepare("INSERT INTO tasks (user_id, title, description, status, due_date) 
                                    VALUES (?, ?, ?, ?, ?)");
        if ($stmt_insert === false) {
            http_response_code(500);
            echo json_encode(["message" => "Database error (POST prepare failed): " . $connection->error]);
            exit;
        }

        $stmt_insert->bind_param("issss", $loggedInUserID, $title, $description, $status, $due_date);

        $title = $input["title"];
        $description = $input["description"];
        $status = $input["status"];
        $due_date = $input["due_date"];

        if (!isset($input["title"], $input["description"], $input["status"], $input["due_date"])
            || empty(trim($input["title"])) || empty(trim($input["description"])) || empty(trim($input["status"]))
            || empty(trim($input["due_date"]))) {
            http_response_code(400);
            echo json_encode(["message" => "Missing one or more required task fields."]);
            exit;
        }
        if (strtotime($input["due_date"]) < time()) {
            http_response_code(400);
            echo json_encode(["message" => "The date field cannot be a past date."]);
            exit;
        }
        if (!in_array($status, array("pending", "in_progress", "done")))
        {
            http_response_code(400);
            echo json_encode(["message" => "Allowed status field values: 'pending', 'in_progress', 'done'."]);
            exit;
        }
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $due_date)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid date format. Use YYYY-MM-DD."]);
            exit;
        }

        try {
            $stmt_insert->execute();
        } catch  (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to insert task: " . $stmt_insert->error]);
            exit;
        }

        echo json_encode(["Message" => "Task has been created successfully"]);
        http_response_code(201);
        break;

    case "PUT":
        // Authenticate
        $id = $_GET["id"];
        $owner_id_stmt = $connection->prepare("SELECT user_id FROM tasks WHERE id = ?");
        if ($owner_id_stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Database error (POST auth prepare failed): " . $connection->error]);
            exit;
        }
        $owner_id_stmt->bind_param("i", $id);
        $owner_id_stmt->execute();

        $result = $owner_id_stmt->get_result();
        $data = $result->fetch_assoc();
        $owner_id = $data["user_id"];

        if ($owner_id != $loggedInUserID) {
            http_response_code(401);
            echo json_encode(["message" => "You are not authorized for this action."]);
            exit;
        }

        $stmt_update = $connection->prepare("UPDATE tasks SET title=?, description=?, status=?, due_date=? 
                                    WHERE id=? AND user_id=? AND is_deleted = 0");
        if ($stmt_update === false) {
            http_response_code(500);
            echo json_encode(["message" => "Database error (PUT prepare failed): " . $connection->error]);
            exit;
        }

        $stmt_update->bind_param("sssssi", $title, $description, $status, $due_date, $id, $loggedInUserID);

        $title = $input["title"];
        $description = $input["description"];
        $status = $input["status"];
        $due_date = $input["due_date"];

        if (!isset($input["title"], $input["description"], $input["status"], $input["due_date"])
            || empty(trim($input["title"])) || empty(trim($input["description"])) || empty(trim($input["status"]))
            || empty(trim($input["due_date"]))) {
            http_response_code(400);
            echo json_encode(["message" => "Missing one or more required task fields."]);
            exit;
        }
        if (strtotime($input["due_date"]) < time()) {
            http_response_code(400);
            echo json_encode(["message" => "The date field cannot be a past date."]);
            exit;
        }
        if (!in_array($status, array("pending", "in_progress", "done")))
        {
            http_response_code(400);
            echo json_encode(["message" => "The status field can be: 'pending', 'in_progress', 'done'."]);
            exit;
        }

        try {
            $stmt_update->execute();
        } catch  (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to insert task: " . $stmt_update->error]);
            exit;
        }

        echo json_encode(["message" => "Task updated successfully"]);
        http_response_code(200);
        break;

    case "DELETE":
        // Authenticate
        $id = $_GET["id"];
        $owner_id_stmt = $connection->prepare("SELECT user_id FROM tasks WHERE id = ?");
        if ($owner_id_stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Database error (DELETE auth prepare failed): " . $connection->error]);
            exit;
        }
        $owner_id_stmt->bind_param("i", $id);
        $owner_id_stmt->execute();

        $result = $owner_id_stmt->get_result();
        $data = $result->fetch_assoc();
        $owner_id = $data["user_id"];

        if ($owner_id != $loggedInUserID) {
            http_response_code(401);
            echo json_encode(["message" => "You are not authorized for this action."]);
            exit;
        }

        $stmt_delete = $connection->prepare("UPDATE tasks SET is_deleted=? WHERE id=?");
        if ($stmt_delete === false) {
            http_response_code(500);
            echo json_encode(["message" => "Database error (DELETE prepare failed): " . $connection->error]);
            exit;
        }

        $stmt_delete->bind_param("ii", $soft_delete ,$id);

        $soft_delete = 1;
        $id = $_GET["id"];

        if (!$stmt_delete->execute()) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete task: " . $stmt_delete->error]);
            $stmt_delete->close();
            exit;
        }

        echo json_encode(["message" => "Task deleted successfully"]);
        http_response_code(200);
        break;

    default:
        echo json_encode(["message" => "Invalid request method"]);
        http_response_code(405);
        break;
}

$connection->close();

?>