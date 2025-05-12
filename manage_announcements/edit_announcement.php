<?php
// เปิดใช้งานการแสดงข้อผิดพลาดสำหรับการดีบัก (ควรปิดใน production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // เริ่มต้น session
require_once '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
require_once '../admin/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// รับค่า ID ที่ต้องการแก้ไข
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID ประกาศไม่ถูกต้อง.";
    header('Location: ../admin/announcements.php');
    exit();
}

$id = intval($_GET['id']);

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        header("Location: edit_announcement.php?id=$id");
        exit();
    }

    // ตรวจสอบว่ามีการอัปโหลดรูปภาพ
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ตรวจสอบไฟล์เป็นรูปภาพจริงหรือไม่
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error'] = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ.";
            header("Location: edit_announcement.php?id=$id");
            exit();
        }

        // ตรวจสอบขนาดไฟล์
        if ($_FILES["image"]["size"] > 5000000) { // ขนาดไฟล์ไม่เกิน 5MB
            $_SESSION['error'] = "ไฟล์รูปภาพมีขนาดใหญ่เกินไป.";
            header("Location: edit_announcement.php?id=$id");
            exit();
        }

        // อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $_SESSION['error'] = "อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG เท่านั้น.";
            header("Location: edit_announcement.php?id=$id");
            exit();
        }

        // อัปโหลดไฟล์
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $image_name;
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์.";
            header("Location: edit_announcement.php?id=$id");
            exit();
        }
    }

    // อัปเดตข้อมูลประกาศลงฐานข้อมูล
    if (!empty($image_url)) {
        $sql = "UPDATE announcements SET title = ?, content = ?, image_url = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $title, $content, $image_url, $id);
    } else {
        $sql = "UPDATE announcements SET title = ?, content = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $title, $content, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "แก้ไขประกาศเรียบร้อยแล้ว!";
        header('Location: ../admin/announcements.php');
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกประกาศ.";
        header("Location: edit_announcement.php?id=$id");
    }

    $stmt->close();
    $conn->close();
    exit();
}

// ดึงข้อมูลประกาศปัจจุบันเพื่อนำมาแสดงในฟอร์ม
$sql = "SELECT * FROM announcements WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$announcement = $result->fetch_assoc();

if (!$announcement) {
    $_SESSION['error'] = "ไม่พบประกาศที่ต้องการแก้ไข.";
    header('Location: ../admin/announcements.php');
    exit();
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">

<?php include '../admin/header.php'; ?>

<body>
    <!-- รวมไฟล์ Navigation -->
    <?php include '../admin/nav.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">แก้ไขประกาศ</h1>

        <!-- การแจ้งเตือน (Flash Message) -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                htmlspecialchars($_SESSION['error']) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label for="title" class="form-label">หัวข้อ</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">เนื้อหา</label>
                <textarea class="form-control" id="content" name="content" rows="5" required><?= htmlspecialchars($announcement['content']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">เลือกรูปภาพใหม่ (ถ้ามี)</label>
                <input type="file" class="form-control" id="image" name="image" accept=".jpg, .jpeg, .png">
                <?php if (!empty($announcement['image_url'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($announcement['image_url']) ?>" alt="รูปประกาศ" style="max-width: 200px; margin-top: 10px;">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
            <a href="../admin/announcements.php" class="btn btn-secondary">ย้อนกลับ</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
