<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการระบบ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ลิงก์ Bootstrap และไอคอน -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* พื้นฐาน */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding-top: 56px; /* ความสูงของ Navbar */
            background-color: #f8f9fa;
        }

        /* Main Content */
        .main-content {
            padding: 20px;
            margin-left: 18%; /* ปรับตาม Sidebar */
            margin-top: 100px; /* ปรับตาม Header */
            background-color: white;
        }

        /* จัดปุ่มให้อยู่ในแนวนอน */
        .button-container {
            display: flex;
            flex-wrap: wrap; /* ให้ปุ่มห่อเมื่อหน้าจอเล็ก */
            justify-content: center; /* จัดปุ่มให้อยู่ตรงกลาง */
            gap: 20px; /* เพิ่มช่องว่างระหว่างปุ่ม */
        }

        .button {
            width: 200px; /* กำหนดความกว้างให้เป็นมาตรฐาน */
            height: 150px; /* กำหนดความสูงให้เป็นมาตรฐาน */
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            padding: 20px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* เงาให้ปุ่ม */
        }

        .button i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .common-fees {
            background-color: #FF3E3E; /* สีสำหรับค่าส่วนกลาง */
        }

        .common-fees1 {
            background-color: #007bff; /* สีสำหรับการแจ้งปัญหา */
        }

        .common-fees2 {
            background-color: #28a745; /* สีสำหรับการเบิกค่าใช้จ่าย */
        }

        .common-fees3 {
            background-color: #17a2b8; /* สีสำหรับประชาสัมพันธ์ */
        }

        .button:hover {
            opacity: 0.9;
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); /* เงาเมื่อ hover */
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0; /* ปรับ margin เมื่อหน้าจอเล็ก */
                margin-top: 70px; /* ปรับตาม Navbar ที่เล็กลง */
            }

            .button {
                height: auto;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

    <!-- นำเข้า header.php -->
    <?php include 'header.php'; ?>
    <!-- นำเข้า nav.php -->
    <?php include 'nav.php'; ?>

    <!-- Main Content -->
    <div class="main-content container-fluid">
        <div class="button-container">
            <a href="common_fees.php" class="button common-fees">
                <i class="bi bi-book"></i>
                <p>จัดการค่าส่วนกลาง</p>
            </a>
            <a href="problems.php" class="button common-fees1">
                <i class="bi bi-lightbulb"></i>
                <p>จัดการการแจ้งปัญหา</p>
            </a>
            <a href="withdrawals.php" class="button common-fees2">
                <i class="bi bi-wallet2"></i>
                <p>จัดการการเบิกค่าใช้จ่าย</p>
            </a>
            <!-- ปุ่มประชาสัมพันธ์ที่เพิ่มเข้ามา -->
            <a href="announcements.php" class="button common-fees3">
                <i class="bi bi-megaphone"></i>
                <p>จัดการประชาสัมพันธ์</p>
            </a>
        </div>
    </div>

    <!-- ลิงก์ JavaScript ของ Bootstrap และ dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
