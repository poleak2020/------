<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
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

        .form-control,
        .btn {
            border-radius: 10px;
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
    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>

    <article>
        <div class="container">
            <h1>เพิ่มผู้ใช้งาน</h1>
            <form id="addUserForm" action="process_add_user.php" method="POST">
                <!-- ช่องสำหรับกรอกบ้านเลขที่ -->
                <div class="mb-3">
                    <label for="house_number" class="form-label">บ้านเลขที่</label>
                    <input type="text" class="form-control" id="house_number" name="house_number" required>
                </div>

                <!-- ช่องสำหรับกรอกแปลงที่ -->
                <div class="mb-3">
                    <label for="plot_number" class="form-label">แปลงที่</label>
                    <input type="text" class="form-control" id="plot_number" name="plot_number" required>
                </div>

                <!-- ช่องสำหรับกรอกเนื้อที่ -->
                <div class="mb-3">
                    <label for="area" class="form-label">เนื้อที่ (ตร.ว.)</label>
                    <input type="number" step="0.01" class="form-control" id="area" name="area" required>
                </div>

                <div class="mb-3">
                    <label for="user_id" class="form-label">ไอดี</label>
                    <input type="text" class="form-control" id="user_id" name="user_id" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="owner_name" class="form-label">เจ้าบ้าน/ผู้ดูแล</label>
                    <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                </div>
                <div class="mb-3">
                    <label for="contact_number" class="form-label">เบอร์ติดต่อ</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" oninput="formatPhoneNumber(this)" required>
                </div>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> บันทึก</button>
                <a href="../admin/users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            </form>
        </div>
    </article>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- สคริปต์จัดรูปแบบเบอร์โทรศัพท์ -->
    <script>
        // ฟังก์ชันจัดรูปแบบเบอร์โทรศัพท์
        function formatPhoneNumber(input) {
            const value = input.value.replace(/\D/g, ''); // ลบตัวอักษรที่ไม่ใช่ตัวเลขทั้งหมดออก
            const length = value.length;

            if (length > 3 && length <= 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3);
            } else if (length > 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
            } else {
                input.value = value;
            }
        }

        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault(); // หยุดการส่งฟอร์มปกติ
            const form = e.target;

            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณต้องการเพิ่มผู้ใช้งานนี้หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, เพิ่มเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // ส่งฟอร์มหากผู้ใช้ยืนยัน
                }
            });
        });
    </script>
</body>

</html>