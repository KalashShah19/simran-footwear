<?php
session_start();
header("Content-Type: application/json");
require 'db.php';

include 'PHPMailer/src/PHPMailer.php';
include 'PHPMailer/src/SMTP.php';
include 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---------------------- Email Sending ----------------------
function sendOtpEmail($toEmail, $otp)
{
    // echo "sending email.";
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'snapdragon7gamer@gmail.com';
        $mail->Password = 'rmug rrjo qfde jxxg';
        $mail->SMTPSecure = "tls";
        $mail->Port = 587;

        $mail->setFrom('simran.footwear@gmail.com', 'Simran Footwear');
        $mail->addAddress($toEmail);
        $mail->Subject = 'Your OTP';
        $mail->isHTML(true);
        $mail->Body = "Your OTP is <b>$otp</b>.";

        $mail->send();
        // echo "Email Sent Successfully !";
        return true;
    } catch (Exception $e) {
        // echo "<script>alert(" . json_encode($e->getMessage()) . ");</script>";
        return $e->getMessage();
    }
}

// ---------------------- Start Login ----------------------
function startLogin($conn, $identifier)
{
    try {
        $stmt = $conn->prepare("SELECT user_id, email, is_verified FROM users WHERE email = ? OR mobile_number = ?");
        if (!$stmt)
            throw new Exception("Prepare failed: " . $conn->error);

        $stmt->bind_param("ss", $identifier, $identifier);
        if (!$stmt->execute())
            throw new Exception("Execute failed: " . $stmt->error);

        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {

            if (!$user['is_verified']) {
                echo json_encode(["status" => false, "message" => "Please verify your email before logging in."]);
                return;
            }

            $otp = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            $stmtUpdate = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?");
            if (!$stmtUpdate)
                throw new Exception("Prepare (update) failed: " . $conn->error);

            $stmtUpdate->bind_param("ssi", $otp, $expiry, $user['user_id']);
            if (!$stmtUpdate->execute())
                throw new Exception("Execute (update) failed: " . $stmtUpdate->error);

            $stmt->close();
            $stmtUpdate->close();

            $flag = sendOtpEmail($user['email'], $otp);
            if ($flag === true) {
                echo json_encode(["status" => true, "message" => "OTP sent to your email."]);
            } else {
                echo json_encode(["status" => false, "message" => $flag]);
            }

        } else {
            echo json_encode(["status" => false, "message" => "User not found with that email or mobile number."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "An error occurred: " . $e->getMessage()]);
    }
}


// ---------------------- Verify OTP ----------------------
function verifyOtp($conn, $identifier, $otp)
{
    $stmt = $conn->prepare("SELECT user_id, otp, otp_expiry, first_name, last_name, email, mobile_number FROM users WHERE email = ? OR mobile_number = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($user['otp'] === $otp) {
            $now = date("Y-m-d H:i:s");
            if ($now > $user['otp_expiry']) {
                echo json_encode(["status" => false, "message" => "OTP expired. Please request a new one."]);
            } else {
                $stmtClear = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE user_id = ?");
                $stmtClear->bind_param("i", $user['user_id']);
                $stmtClear->execute();

                echo json_encode([
                    "status" => true,
                    "message" => "Login successful.",
                    "user" => [
                        "user_id" => $user['user_id'],
                        "first_name" => $user['first_name'],
                        "last_name" => $user['last_name'],
                        "email" => $user['email'],
                        "mobile_number" => $user['mobile_number']
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

// ---------------------- Register User ----------------------
function registerUser($conn, $data)
{
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $mobile_number = trim($data['mobile_number'] ?? '');

    if (!$first_name || !$last_name || !$email || !$mobile_number) {
        echo json_encode(["status" => false, "message" => "All fields are required."]);
        return;
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR mobile_number = ?");
    $stmt->bind_param("ss", $email, $mobile_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(["status" => false, "message" => "User already exists."]);
        return;
    }

    // Generate OTP and store data in session
    $otp = rand(100000, 999999);

    $_SESSION['registration'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'mobile_number' => $mobile_number,
        'otp' => $otp
    ];

    if (sendOtpEmail($email, $otp)) {
        echo json_encode(["status" => true, "message" => "OTP sent to email. Please verify."]);
    } else {
        echo json_encode(["status" => false, "message" => "Failed to send OTP."]);
    }
}

// ---------------------- OTP Verification for Registration ----------------------
function verifyRegistrationOtp($conn, $identifier, $otp)
{
    if (!isset($_SESSION['registration'])) {
        echo json_encode(["status" => false, "message" => "No registration in progress."]);
        return;
    }

    $sessionData = $_SESSION['registration'];

    // Check if identifier matches (email or mobile)
    if ($identifier !== $sessionData['email'] && $identifier !== $sessionData['mobile_number']) {
        echo json_encode(["status" => false, "message" => "Invalid identifier."]);
        return;
    }

    if ($otp != $sessionData['otp']) {
        echo json_encode(["status" => false, "message" => "Invalid OTP."]);
        return;
    }

    // Insert into users table
    $stmtInsert = $conn->prepare(
        "INSERT INTO users (first_name, last_name, email, mobile_number, is_verified) VALUES (?, ?, ?, ?, 1)"
    );
    $stmtInsert->bind_param(
        "ssss",
        $sessionData['first_name'],
        $sessionData['last_name'],
        $sessionData['email'],
        $sessionData['mobile_number']
    );

    if ($stmtInsert->execute()) {
        // Clear session after successful registration
        unset($_SESSION['registration']);
        echo json_encode(["status" => true, "message" => "Registration successful. You can now login."]);
    } else {
        echo json_encode(["status" => false, "message" => "Failed to complete registration."]);
    }
}

// ---------------------- Handle Incoming Request ----------------------
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
        registerUser($conn, $data);
        break;

    case 'verify_registration_otp':
        verifyRegistrationOtp($conn, $data['identifier'], $data['otp']);
        break;

    default:
        echo json_encode(["status" => false, "message" => "Invalid action."]);
}
