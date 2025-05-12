<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php
    // เชื่อมต่อฐานข้อมูล
    require '../admin/db_connection.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // รับข้อมูลจากฟอร์มและ trim ช่องว่าง
        $house_number = trim($_POST['house_number']);
        $plot_number = trim($_POST['plot_number']);
        $area = trim($_POST['area']); // รับข้อมูลเนื้อที่
        $user_id = trim($_POST['user_id']);
        $password = trim($_POST['password']);
        $owner_name = trim($_POST['owner_name']);
        $contact_number = trim($_POST['contact_number']);

        // ตรวจสอบว่ามีข้อมูลครบถ้วนหรือไม่
        if (empty($house_number) || empty($plot_number) || empty($user_id) || empty($password) || empty($owner_name) || empty($contact_number) || empty($area)) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
              </script>";
            exit();
        }

        // ตรวจสอบรูปแบบบ้านเลขที่ เช่น 109/80
        if (!preg_match('/^[0-9]+\/[0-9]+$/', $house_number)) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'รูปแบบบ้านเลขที่ไม่ถูกต้อง กรุณากรอกในรูปแบบ เลข/เลข',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
              </script>";
            exit();
        }

        // ตรวจสอบรูปแบบแปลงที่
        if (!preg_match('/^[0-9]+$/', $plot_number)) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'รูปแบบแปลงที่ไม่ถูกต้อง กรุณากรอกเป็นตัวเลขเท่านั้น',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
              </script>";
            exit();
        }

        // ตรวจสอบรูปแบบเนื้อที่ว่ามีแต่ตัวเลขและไม่เป็นค่าว่าง
        if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $area)) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'กรุณากรอกเนื้อที่เป็นตัวเลขเท่านั้น',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
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
                    window.history.back();
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
                    window.history.back();
                });
              </script>";
            exit();
        }

        // ตรวจสอบความปลอดภัยของรหัสผ่าน
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


        // ตรวจสอบว่ามี house_number นี้แล้วหรือไม่
        $sql_check_house = "SELECT * FROM users WHERE house_number = ?";
        $stmt_check_house = $conn->prepare($sql_check_house);
        $stmt_check_house->bind_param("s", $house_number);
        $stmt_check_house->execute();
        $result_check_house = $stmt_check_house->get_result();

        if ($result_check_house->num_rows > 0) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'บ้านเลขที่นี้มีอยู่แล้วในระบบ',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
              </script>";
            exit();
        }
        $stmt_check_house->close();

        // ตรวจสอบว่ามีแปลงที่นี้แล้วหรือไม่
        $sql_check_plot = "SELECT * FROM users WHERE plot_number = ?";
        $stmt_check_plot = $conn->prepare($sql_check_plot);
        $stmt_check_plot->bind_param("s", $plot_number);
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
                    window.history.back();
                });
              </script>";
            exit();
        }
        $stmt_check_plot->close();

        // ตรวจสอบว่ามี user_id นี้แล้วหรือไม่
        $sql_check_user = "SELECT * FROM users WHERE user_id = ?";
        $stmt_check_user = $conn->prepare($sql_check_user);
        $stmt_check_user->bind_param("s", $user_id);
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
                    window.history.back();
                });
              </script>";
            exit();
        }
        $stmt_check_user->close();

        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // เตรียมคำสั่ง SQL เพื่อเพิ่มผู้ใช้งาน
        $sql = "INSERT INTO users (house_number, plot_number, area, user_id, password, owner_name, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $house_number, $plot_number, $area, $user_id, $hashed_password, $owner_name, $contact_number);

        // ดำเนินการคำสั่ง SQL
        if ($stmt->execute()) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'เพิ่มผู้ใช้งานสำเร็จ',
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.href = '../admin/users.php';
                });
              </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด: " . addslashes($stmt->error) . "' ,
                    showCloseButton: true,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
              </script>";
        }

        // ปิดการเชื่อมต่อฐานข้อมูล
        $stmt->close();
        $conn->close();
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'การส่งข้อมูลผิดพลาด',
                showCloseButton: true,
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.history.back();
            });
          </script>";
    }
    ?>

</body>

</html>