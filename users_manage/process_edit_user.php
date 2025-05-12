<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้งาน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>
<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
require '../admin/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $plot_number = isset($_POST['plot_number']) ? trim($_POST['plot_number']) : '';
    $house_number = isset($_POST['house_number']) ? trim($_POST['house_number']) : '';
    $area = isset($_POST['area']) ? trim($_POST['area']) : '';
    $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $owner_name = isset($_POST['owner_name']) ? trim($_POST['owner_name']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';

    // ตรวจสอบว่ามีข้อมูลครบหรือไม่
    if (empty($id) || empty($plot_number) || empty($house_number) || empty($area) || empty($user_id) || empty($owner_name) || empty($contact_number)) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }

    // ตรวจสอบรูปแบบบ้านเลขที่
    if (!preg_match('/^[0-9]+\/[0-9]+$/', $house_number)) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'รูปแบบบ้านเลขที่ไม่ถูกต้อง กรุณากรอกในรูปแบบ เลข/เลข',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }

    // ตรวจสอบว่าเนื้อที่เป็นตัวเลขและมากกว่า 0
    if (!is_numeric($area) || $area <= 0) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'เนื้อที่ต้องเป็นตัวเลขและมากกว่า 0',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }

    // ตรวจสอบรูปแบบเบอร์ติดต่อ
    if (!preg_match('/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/', $contact_number)) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'เบอร์ติดต่อไม่ถูกต้อง กรุณากรอกในรูปแบบ 000-000-0000',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }

    // ตรวจสอบรูปแบบ user_id
    if (!preg_match('/^[a-zA-Z0-9]{6,15}$/', $user_id)) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'ไอดีต้องประกอบด้วยตัวอักษรและตัวเลข 6-15 ตัว',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }

    // ตรวจสอบการเปลี่ยนแปลงรหัสผ่าน
    if (!empty($password)) {
        // ตรวจสอบรูปแบบรหัสผ่าน
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/', $password)) {
            echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'รหัสผ่านต้องมีอย่างน้อย 6 ตัว และประกอบด้วยตัวพิมพ์เล็ก, ตัวพิมพ์ใหญ่, ตัวเลข และสัญลักษณ์',
                        showCloseButton: true,
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.history.back();
                    });
                  </script>";
            exit();
        }


        // เข้ารหัสรหัสผ่านหากผู้ใช้ป้อนรหัสผ่านใหม่
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        // หากไม่ป้อนรหัสผ่านใหม่ ให้ใช้รหัสผ่านเดิมจากฐานข้อมูล
        $sql_get_password = "SELECT password FROM users WHERE id = ?";
        $stmt_get_password = $conn->prepare($sql_get_password);
        $stmt_get_password->bind_param("i", $id);
        $stmt_get_password->execute();
        $result_password = $stmt_get_password->get_result();
        if ($result_password->num_rows > 0) {
            $hashed_password = $result_password->fetch_assoc()['password'];
        }
        $stmt_get_password->close();
    }

    // ตรวจสอบว่ามี user_id นี้ในระบบอยู่แล้วหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
    $sql_check_user = "SELECT * FROM users WHERE user_id = ? AND id != ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    $stmt_check_user->bind_param("si", $user_id, $id);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();

    if ($result_check_user->num_rows > 0) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'มีไอดีนี้แล้วในระบบ',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }
    $stmt_check_user->close();

    // ตรวจสอบว่าแปลงที่ซ้ำกันหรือไม่ (ยกเว้นผู้ใช้ปัจจุบัน)
    $sql_check_plot = "SELECT * FROM users WHERE plot_number = ? AND id != ?";
    $stmt_check_plot = $conn->prepare($sql_check_plot);
    $stmt_check_plot->bind_param("si", $plot_number, $id);
    $stmt_check_plot->execute();
    $result_check_plot = $stmt_check_plot->get_result();

    if ($result_check_plot->num_rows > 0) {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'แปลงที่นี้มีอยู่แล้วในระบบ',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }
    $stmt_check_plot->close();

    // เตรียมคำสั่ง SQL
    $sql = "UPDATE users SET plot_number = ?, house_number = ?, area = ?, user_id = ?, password = ?, owner_name = ?, contact_number = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $plot_number, $house_number, $area, $user_id, $hashed_password, $owner_name, $contact_number, $id);

    if ($stmt->execute()) {
        echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'อัปเดตผู้ใช้งานสำเร็จ',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../admin/users.php';
                });
              </script>";
        exit();
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด: " . addslashes($stmt->error) . "',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../users_manage/edit_user.php?id=$id';
                });
              </script>";
        exit();
    }
} else {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'การส่งข้อมูลผิดพลาด',
                showCloseButton: true,
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = '../admin/users.php';
            });
          </script>";
    exit();
}
?>