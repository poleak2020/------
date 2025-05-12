<?php
// เปิดใช้งานการแสดงข้อผิดพลาดสำหรับการดีบัก (ควรปิดใน production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // เริ่มต้น session
require_once '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
require_once '../admin/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// สร้าง CSRF token หากยังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบ CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // รับข้อมูลจากฟอร์ม
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image_url = '';

    // ตรวจสอบว่าฟิลด์ title และ content ไม่ว่าง
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน.";
        header('Location: ../manage_announcements/add_announcements.php');
        exit();
    }

    // ตรวจสอบการอัปโหลดรูปภาพ
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../uploads/";
        // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกันด้วย uniqid() และ random_bytes
        $unique_name = bin2hex(random_bytes(5)); // ชื่อแบบสุ่ม 10 ตัวอักษร
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $image_name = $unique_name . "." . $imageFileType; // สร้างชื่อไฟล์ใหม่
        $target_file = $target_dir . $image_name;

        // ตรวจสอบไฟล์เป็นรูปภาพจริงหรือไม่
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error'] = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ.";
            header('Location: ../manage_announcements/add_announcements.php');
            exit();
        }

        // ตรวจสอบขนาดไฟล์
        if ($_FILES["image"]["size"] > 5000000) { // ขนาดไฟล์ไม่เกิน 5MB
            $_SESSION['error'] = "ไฟล์รูปภาพมีขนาดใหญ่เกินไป.";
            header('Location: ../manage_announcements/add_announcements.php');
            exit();
        }

        // อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $_SESSION['error'] = "อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG เท่านั้น.";
            header('Location: ../manage_announcements/add_announcements.php');
            exit();
        }

        // อัปโหลดไฟล์
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $image_name; // เก็บเฉพาะชื่อไฟล์
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์.";
            header('Location: ../manage_announcements/add_announcements.php');
            exit();
        }
    }

    // บันทึกข้อมูลประกาศลงฐานข้อมูล
    $sql = "INSERT INTO announcements (title, content, image_url, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("sss", $title, $content, $image_url);

    if ($stmt->execute()) {
        $_SESSION['message'] = "ประกาศถูกเพิ่มเรียบร้อยแล้ว!";
        header('Location: ../admin/announcements.php');
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกประกาศ.";
        header('Location: ../manage_announcements/add_announcements.php');
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มประกาศใหม่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- รวมไฟล์ Header -->
    <?php include '../admin/header.php'; ?>
    <!-- รวมไฟล์ Navigation -->
    <?php include '../admin/nav.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">เพิ่มประกาศใหม่</h1>

        <!-- การแจ้งเตือน (Flash Message) -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                htmlspecialchars($_SESSION['error']) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
                htmlspecialchars($_SESSION['message']) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>';
            unset($_SESSION['message']);
        }
        ?>

        <form id="announcementForm" method="POST" enctype="multipart/form-data" onsubmit="return confirmSubmit()">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label for="title" class="form-label">หัวข้อ</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">เนื้อหา</label>
                <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">เลือกรูปภาพ (ถ้ามี)</label>
                <input type="file" class="form-control" id="image" name="image" accept=".jpg, .jpeg, .png">
                <img id="previewImage" src="" alt="" style="max-width: 200px; margin-top: 10px; display: none;">
            </div>

            <button type="submit" class="btn btn-primary">เพิ่มประกาศ</button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='../admin/announcements.php';">ย้อนกลับ</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // แสดงภาพตัวอย่างเมื่อมีการเลือกรูปภาพ
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImage = document.getElementById('previewImage');
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // ยืนยันการส่งฟอร์ม
        function confirmSubmit() {
            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;
            const imageSrc = document.getElementById('previewImage').src;
            const hasImage = document.getElementById('image').files.length > 0;

            let imageHtml = '';
            if (hasImage) {
                imageHtml = `<img src="${imageSrc}" alt="Image" style="max-width: 200px; margin-top: 10px;">`;
            }

            Swal.fire({
                title: 'ยืนยันการเพิ่มประกาศ',
                html: `<strong>หัวข้อ:</strong> ${title}<br><strong>เนื้อหา:</strong> ${content}<br>${imageHtml}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'เพิ่มประกาศ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('announcementForm').submit();
                }
            });

            return false; // ป้องกันการส่งฟอร์มทันที
        }
    </script>
</body>

</html>