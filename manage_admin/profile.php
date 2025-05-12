<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            color: #495057;
            padding-top: 60px;
        }

        .profile-container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 10px;
        }

        .profile-header p {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .profile-info {
            margin-top: 20px;
        }

        .profile-info h5 {
            font-size: 1.3rem;
            font-weight: 500;
            color: #343a40;
            margin-bottom: 15px;
        }

        .profile-info p {
            font-size: 1.1rem;
            color: #555;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 25px;
            padding: 12px 24px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }
    </style>
</head>

<body>
    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>

    <div class="container">
        <a href="javascript:history.back()" class="btn btn-back mb-4">
            <i class="bi bi-arrow-left"></i> ย้อนกลับ
        </a>

        <div class="profile-container">
            <div class="profile-header">
                <h2>โปรไฟล์ของคุณ</h2>
                <p>ข้อมูลส่วนตัวของคุณ</p>
            </div>
            <div class="profile-info">
                <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $servername = "localhost";
                $db_username = "root";
                $db_password = "";
                $dbname = "corporation";

                // ตรวจสอบการเชื่อมต่อฐานข้อมูล
                $conn = new mysqli($servername, $db_username, $db_password, $dbname);
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // ตรวจสอบว่ามีการตั้งค่า ID ในเซสชันหรือไม่
                if (!isset($_SESSION['user_id'])) {
                    echo "Error: User ID not found in session.";
                    exit;
                }

                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT full_name, email, phone_number FROM admin WHERE id = ?");
                if ($stmt === false) {
                    die("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user === null) {
                    echo "Error: User not found.";
                } else {
                ?>
                    <h5>ชื่อ: <?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p>อีเมล: <?php echo htmlspecialchars($user['email']); ?></p>
                    <p>เบอร์โทรศัพท์: <?php echo htmlspecialchars($user['phone_number']); ?></p>
                    <a href="../manage_admin/edit_profile.php" class="btn btn-primary mt-3">แก้ไขโปรไฟล์</a>
                <?php
                }
 
                $stmt->close();
                $conn->close();
                ?>
            </div>
        </div>
    </div>
</body>

</html>
