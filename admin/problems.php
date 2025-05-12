<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการปัญหา</title>
    <!-- เพิ่ม jQuery และ Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- เพิ่ม SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            margin-top: 150px;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #1363FD;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .problem-list {
            margin-top: 30px;
        }

        .problem-item {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .problem-item h5 {
            margin-bottom: 15px;
        }

        .footer {
            background-color: #7BC59D;
            width: 100%;
            height: 40px;
            text-align: center;
            padding: 10px;
            position: fixed;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .footer p {
            margin: 0;
            color: white;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            background-color: #e9ecef;
            margin: 0 5px;
        }

        .tab.active {
            background-color: #1363FD;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        article {
            margin-left: 0%;
            padding: 0px;
            background-color: white;
            margin-top: 100px;
        }

        .search-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .rows-per-page {
            display: flex;
            align-items: center;
        }

        .rows-per-page label {
            margin-right: 10px;
        }

        .input-group {
            max-width: 300px;
            width: 100%;
        }

        .icon-container {
            text-align: center;
        }

        .icon-container i {
            font-size: 24px;
            cursor: pointer;
        }

        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: white;
        }

        .btn-back:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }

        .btn-back-wrapper {
            display: flex;
            justify-content: space-between;
            /* แก้ไขจาก justify-content: flex-start; */
            align-items: center;
            margin-bottom: 20px;
        }

        /* เพิ่มการจัดศูนย์กลางปุ่มรายงาน */
        .report-button-wrapper {
            flex: 1;
            text-align: center;
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
            <div class="btn-back-wrapper">
                <a href="../admin/manage.php" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> ย้อนกลับ
                </a>
                <div class="report-button-wrapper">
                    <a href="../admin/generate_report.php" id="generateReport" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> รายงานการแจ้งปัญหา
                    </a>
                </div>

            </div>
            <div class="tabs">
                <div class="tab active" data-tab="pending">ขอดำเนินการ</div>
                <div class="tab" data-tab="received">รับเรื่องเเล้ว</div>
                <div class="tab" data-tab="resolved">แก้ไขเเล้ว</div>
            </div>

            <!-- ฟอร์มค้นหาและเลือกจำนวนแถว -->
            <form method="GET" action="" class="search-form">
                <div class="rows-per-page">
                    <label for="rows" class="form-label">แสดง:</label>
                    <select id="rows" name="rows" class="form-select" style="width: auto;">
                        <option value="10" <?php if (isset($_GET['rows']) && $_GET['rows'] == 10) echo 'selected'; ?>>10</option>
                        <option value="20" <?php if (isset($_GET['rows']) && $_GET['rows'] == 20) echo 'selected'; ?>>20</option>
                        <option value="50" <?php if (isset($_GET['rows']) && $_GET['rows'] == 50) echo 'selected'; ?>>50</option>
                    </select>
                </div>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="ค้นหาบ้านเลขที่" name="house_number" value="<?php echo isset($_GET['house_number']) ? htmlspecialchars($_GET['house_number']) : ''; ?>">
                    <button class="btn btn-success" type="submit">ค้นหา</button>
                </div>
            </form>

            <div class="tab-content active" id="pending">
                <!-- ปรับส่วนนี้ให้สามารถอัปเดตแบบ Ajax -->
                <div id="pending-content">
                    <?php include '../manage_problems/pending_problems.php'; ?>
                </div>
            </div>
            <div class="tab-content" id="received">
                <!-- ปรับส่วนนี้ให้สามารถอัปเดตแบบ Ajax -->
                <div id="received-content">
                    <?php include '../manage_problems/received_problems.php'; ?>
                </div>
            </div>
            <div class="tab-content" id="resolved">
                <!-- ปรับส่วนนี้ให้สามารถอัปเดตแบบ Ajax -->
                <div id="resolved-content">
                    <?php include '../manage_problems/resolved_problems.php'; ?>
                </div>
            </div>
        </div>
    </article>

    <div class="footer">
        <p>เว็บไซต์นี้ทั้งหมดได้รับการคุ้มครองลิขสิทธิ์ 2024 - พัฒนาระบบโดยทีมงานวิทยาการคอมพิวเตอร์ (สมุทรปราการ) มหาวิทยาลัยราชภัฏธนบุรี</p>
    </div>

    <script>
        $(document).ready(function() {
            // ฟังก์ชันในการโหลดข้อมูลแท็บ
            function loadTabContent(tab) {
                $.ajax({
                    url: '../manage_problems/' + tab + '_problems.php',
                    success: function(response) {
                        $('#' + tab + '-content').html(response);
                    }
                });
            }

            // อัปเดตข้อมูลในแต่ละแท็บทุก 3 วินาที (3000 มิลลิวินาที)
            setInterval(function() {
                if ($('#pending').hasClass('active')) {
                    loadTabContent('pending');
                } else if ($('#received').hasClass('active')) {
                    loadTabContent('received');
                } else if ($('#resolved').hasClass('active')) {
                    loadTabContent('resolved');
                }
            }, 3000);

            // การจัดการการคลิกแท็บ
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    Swal.fire({
                        title: 'กำลังโหลดข้อมูล',
                        text: 'โปรดรอสักครู่...',
                        icon: 'info',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
                    loadTabContent(tab.getAttribute('data-tab'));
                });
            });

            // การจัดการการลบปัญหา
            window.deleteProblem = function(problemId) {
                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: "คุณต้องการลบปัญหานี้หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ใช่, ลบมัน!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../manage_problems/delete_problem.php', // ใช้ไฟล์ PHP สำหรับการลบข้อมูล
                            method: 'POST',
                            data: {
                                id: problemId
                            }, // ส่ง ID ของปัญหาที่ต้องการลบ
                            success: function(response) {
                                if (response === 'success') {
                                    Swal.fire(
                                        'ลบแล้ว!',
                                        'ปัญหานี้ได้ถูกลบแล้ว.',
                                        'success'
                                    );
                                    // โหลดข้อมูลใหม่หลังจากลบเสร็จ
                                    if ($('#pending').hasClass('active')) {
                                        loadTabContent('pending');
                                    } else if ($('#received').hasClass('active')) {
                                        loadTabContent('received');
                                    } else if ($('#resolved').hasClass('active')) {
                                        loadTabContent('resolved');
                                    }
                                } else {
                                    Swal.fire(
                                        'เกิดข้อผิดพลาด!',
                                        'ไม่สามารถลบปัญหาได้.',
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                Swal.fire(
                                    'เกิดข้อผิดพลาด!',
                                    'ไม่สามารถลบปัญหาได้.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            }

        });
    </script>

</body>

</html>