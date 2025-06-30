<?php
header("Content-Type: application/json");
require 'db.php';

function sendOtpEmail($toEmail, $otp) {
    $subject = "OTP";
    $message = "Your One-Time Password (OTP) is: $otp\nPlease do not share it with anyone.";
    $headers = "From: snapdragon7gamer@gamil.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    return mail($toEmail, $subject, $message, $headers);
}

// Function to start login process and send OTP
function startLogin($conn, $identifier) {
    // $identifier can be email or mobile number

    // Find user by email or mobile
    $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE email = ? OR mobile_number = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Generate OTP and expiry (e.g., 5 mins from now)
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // Save OTP and expiry in DB
        $stmtUpdate = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?");
        $stmtUpdate->bind_param("ssi", $otp, $expiry, $user['user_id']);
        $stmtUpdate->execute();

        // Send OTP via email
        if (sendOtpEmail($user['email'], $otp)) {
            echo json_encode(["status" => true, "message" => "OTP sent to your email."]);
        } else {
            echo json_encode(["status" => false, "message" => "Failed to send OTP email."]);
        }
    } else {
        echo json_encode(["status" => false, "message" => "User not found with that email or mobile number."]);
    }
}

// Function to verify OTP
function verifyOtp($conn, $identifier, $otp) {
    // Find user by email or mobile
    $stmt = $conn->prepare("SELECT user_id, otp, otp_expiry, first_name, last_name, email, mobile_number, gender FROM users WHERE email = ? OR mobile_number = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($user['otp'] === $otp) {
            $now = date("Y-m-d H:i:s");
            if ($now > $user['otp_expiry']) {
                echo json_encode(["status" => false, "message" => "OTP expired. Please request a new one."]);
            } else {
                // Clear OTP after successful login
                $stmtClear = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE user_id = ?");
                $stmtClear->bind_param("i", $user['user_id']);
                $stmtClear->execute();

                // Return user data on successful login
                echo json_encode([
                    "status" => true,
                    "message" => "Login successful.",
                    "user" => [
                        "user_id" => $user['user_id'],
                        "first_name" => $user['first_name'],
                        "last_name" => $user['last_name'],
                        "email" => $user['email'],
                        "mobile_number" => $user['mobile_number'],
                        "gender" => $user['gender']
                    ]
                ]);
            }
        } else {
            echo json_encode(["status" => false, "message" => "Invalid OTP."]);
        }
    } else {
        echo json_encode(["status" => false, "message" => "User not found."]);
    }
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'start_login':
        $identifier = $data['identifier'] ?? '';
        if (!$identifier) {
            echo json_encode(["status" => false, "message" => "Email or Mobile number required."]);
            exit;
        }
        startLogin($conn, $identifier);
        break;

    case 'verify_otp':
        $identifier = $data['identifier'] ?? '';
        $otp = $data['otp'] ?? '';
        if (!$identifier || !$otp) {
            echo json_encode(["status" => false, "message" => "Email/Mobile and OTP required."]);
            exit;
        }
        verifyOtp($conn, $identifier, $otp);
        break;

    case 'register':
        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $mobile_number = trim($data['mobile_number'] ?? '');
        $gender = trim($data['gender'] ?? '');
        

        // Basic validation
        if (!$first_name || !$last_name || !$email || !$mobile_number || !$gender) {
            echo json_encode(["status" => false, "message" => "All fields are required."]);
            exit;
        }

        // Check if user already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR mobile_number = ?");
        $stmt->bind_param("ss", $email, $mobile_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["status" => false, "message" => "User already exists."]);
            exit;
        }

        // Generate OTP and expiry
        $otp = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // Insert user
        $stmtInsert = $conn->prepare("INSERT INTO users (first_name, last_name, email, mobile_number, gender, otp, otp_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("sssssss", $first_name, $last_name, $email, $mobile_number, $gender, $otp, $otp_expiry);
        
        if ($stmtInsert->execute()) {
            if (sendOtpEmail($email, $otp)) {
                echo json_encode(["status" => true, "message" => "Registration successful. OTP sent to email."]);
            } else {
                echo json_encode(["status" => false, "message" => "User registered, but failed to send OTP."]);
            }
        } else {
            echo json_encode(["status" => false, "message" => "Registration failed."]);
        }
        break;


    default:
        echo json_encode(["status" => false, "message" => "Invalid action."]);
}
