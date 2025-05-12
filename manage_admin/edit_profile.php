<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
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
$error = "";

// ตรวจสอบว่าฟอร์มถูกส่งหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);

    // ตรวจสอบฟิลด์ที่กรอกข้อมูล
    if (empty($full_name) || empty($email)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน.";
    } else {
        // อัปเดตข้อมูลในฐานข้อมูล
        $stmt = $conn->prepare("UPDATE admin SET full_name = ?, email = ?, phone_number = ? WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssi", $full_name, $email, $phone_number, $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "อัปเดตโปรไฟล์เรียบร้อยแล้ว!";
            header("Location: profile.php");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปเดตโปรไฟล์.";
        }
        $stmt->close();
    }
}

// ดึงข้อมูลโปรไฟล์ปัจจุบัน
$stmt = $conn->prepare("SELECT full_name, email, phone_number FROM admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโปรไฟล์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .profile-info {
            margin-top: 20px;
        }

        .profile-info label {
            font-weight: 500;
            font-size: 1.1rem;
        }

        .profile-info input {
            font-size: 1.1rem;
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
        <div class="profile-container">
            <div class="profile-header">
                <h2>แก้ไขโปรไฟล์ของคุณ</h2>
            </div>

            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form id="profileForm" method="POST" action="edit_profile.php" onsubmit="return confirmSubmit();">
                <div class="mb-3">
                    <label for="full_name" class="form-label">ชื่อ</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>">
                </div>

                <div class="d-flex justify-content-between">
                    <!-- ปุ่มย้อนกลับ -->
                    <a href="javascript:history.back()" class="btn btn-back">
                        <i class="bi bi-arrow-left"></i> ย้อนกลับ
                    </a>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmSubmit() {
            const fullName = document.getElementById('full_name').value;
            const email = document.getElementById('email').value;
            const phoneNumber = document.getElementById('phone_number').value || 'ไม่มีข้อมูล';

            Swal.fire({
                title: 'ยืนยันการเปลี่ยนแปลง',
                html: `<strong>ชื่อ:</strong> ${fullName}<br><strong>อีเมล:</strong> ${email}<br><strong>เบอร์โทรศัพท์:</strong> ${phoneNumber}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('profileForm').submit();
                }
            });

            return false; // ป้องกันการส่งฟอร์มทันที
        }
    </script>
</body>

</html>

