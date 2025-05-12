<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #f0f8ff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #5E5E5E;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        nav {
            position: fixed;
            top: 105px;
            left: 0;
            width: 18%;
            height: calc(100% - 100px);
            background: #7BC59D;
            padding: 20px;
            overflow: auto;
            z-index: 1000;
        }

        article {
            margin-left: 0%;
            padding: 20px;
            background-color: white;
            margin-top: 100px;
        }
    </style>
</head>

<body>
    <?php
    // เชื่อมต่อฐานข้อมูล
    include '../admin/db_connection.php';

    if ($conn->connect_error) {
        die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }

    // รับ ID จาก URL
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // ดึงข้อมูลผู้ใช้งานจากฐานข้อมูล
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("ไม่พบผู้ใช้งานที่ต้องการแก้ไข");
    }

    // ปิดการเชื่อมต่อฐานข้อมูล
    $conn->close();
    ?>

    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>
    
    <article>
        <div class="container">
            <h1>แก้ไขผู้ใช้งาน</h1>
            <form action="../users_manage/process_edit_user.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <div class="mb-3">
                    <label for="plot_number" class="form-label">แปลงที่</label>
                    <input type="text" class="form-control" id="plot_number" name="plot_number" value="<?php echo htmlspecialchars($user['plot_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="area" class="form-label">เนื้อที่ (ตร.ว.)</label>
                    <input type="number" step="0.01" class="form-control" id="area" name="area" value="<?php echo htmlspecialchars($user['area'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="house_number" class="form-label">บ้านเลขที่</label>
                    <input type="text" class="form-control" id="house_number" name="house_number" value="<?php echo htmlspecialchars($user['house_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="user_id" class="form-label">ไอดี</label>
                    <input type="text" class="form-control" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน (กรอกหากต้องการเปลี่ยน)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>

                <div class="mb-3">
                    <label for="owner_name" class="form-label">เจ้าบ้าน/ผู้ดูแล</label>
                    <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($user['owner_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label">เบอร์ติดต่อ</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'], ENT_QUOTES, 'UTF-8'); ?>" oninput="formatPhoneNumber(this)" required>
                </div>

                <button type="button" class="btn btn-success" id="submitBtn"><i class="bi bi-check-circle"></i> บันทึก</button>
                <a href="../admin/users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            </form>
        </div>
    </article>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function formatPhoneNumber(input) {
            const value = input.value.replace(/\D/g, ''); // ลบตัวอักษรที่ไม่ใช่ตัวเลขทั้งหมดออก
            const length = value.length;

            if (length > 3 && length <= 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3);
            } else if (length > 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6);
            } else {
                input.value = value;
            }
        }

        document.getElementById('submitBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'ยืนยันการบันทึก?',
                text: 'คุณต้องการบันทึกข้อมูลนี้หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').submit(); // ส่งฟอร์มเมื่อยืนยัน
                }
            });
        });
    </script>

</body>

</html>
