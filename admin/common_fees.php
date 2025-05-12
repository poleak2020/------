<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>จัดการค่าส่วนกลาง</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">
    <style>
        /* CSS ที่มีอยู่แล้ว */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
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
            font-size: 20px;
        }

        .header-left {
            font-size: 16px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .dropdown-menu {
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        }

        .dropdown-menu li a:hover {
            background-color: #f1f1f1;
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
            font-size: 17px;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        nav ul li {
            margin-bottom: 10px;
        }

        nav ul li a {
            text-decoration: none;
            color: #333;
            display: block;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #f0f0f0;
            border-radius: 10px;
            color: #0066FF;
        }

        section::after {
            content: "";
            display: table;
            clear: both;
        }

        @media (max-width: 600px) {

            nav,
            article {
                width: 100%;
                height: auto;
            }
        }

        article {
            float: left;
            padding: 20px;
            width: 82%;
            background-color: white;
            text-align: center;
            margin-top: 100px;
            margin-left: 10%;
        }

        .button {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0056b3;
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

        table {
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            text-align: center;
            padding: 8px;
        }

        th {
            background-color: #f8f9fa;
        }

        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
            display: inline-block;
        }

        .btn-back:hover {
            background-color: #5a6268;
            border-color: #4e555b;
        }

        .btn-back-wrapper {
            display: flex;
            justify-content: flex-start;
        }

        .pagination {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <!-- นำเข้าฟาย header.php -->
    <?php include '../admin/header.php'; ?>

    <!-- นำเข้าฟาย nav.php -->
    <?php include '../admin/nav.php'; ?>

    <section>
        <article>
            <div class="btn-back-wrapper">
                <a href="../admin/manage.php" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> ย้อนกลับ
                </a>
            </div>
            <h1>จัดการค่าส่วนกลาง</h1>
            <p>นี่คือหน้าจัดการค่าส่วนกลาง คุณสามารถเพิ่ม แก้ไข หรือ ลบข้อมูลค่าส่วนกลางได้ที่นี่</p>

            <div class="button-group d-flex flex-wrap justify-content-center mb-4">
                <a href="../manage_payment/admin_verify_payments.php" class="btn btn-primary mx-2 mb-2">
                    <i class="bi bi-check-circle"></i> ตรวจสอบการชำระเงินที่เข้ามา
                </a>
                <a href="../manage_payment/payment.php" class="btn btn-success mx-2 mb-2">
                    <i class="bi bi-credit-card"></i> ชำระค่าส่วนกลาง
                </a>
                <a href="../admin/common_fee_report.php" class="btn btn-info mx-2 mb-2">
                    <i class="bi bi-file-earmark-text"></i> รายงานการชำระเงิน
                </a>
            </div>



            <!-- ฟอร์มค้นหาและเลือกจำนวนแถวที่จะแสดง -->
            <form method="GET" action="" class="search-form">
                <div class="rows-per-page">
                    <label for="rows" class="form-label">แสดง:</label>
                    <select id="rows" name="rows" class="form-select" style="width: auto;">
                        <option value="1" <?php if (isset($_GET['rows']) && $_GET['rows'] == 1) echo 'selected'; ?>>1</option>
                        <option value="5" <?php if (isset($_GET['rows']) && $_GET['rows'] == 5) echo 'selected'; ?>>5</option>
                        <option value="10" <?php if (isset($_GET['rows']) && $_GET['rows'] == 10) echo 'selected'; ?>>10</option>
                    </select>
                </div>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="ค้นหาบ้านเลขที่" name="house_number" value="<?php echo isset($_GET['house_number']) ? htmlspecialchars($_GET['house_number']) : ''; ?>">
                    <button class="btn btn-success" type="submit">ค้นหา</button>
                </div>
            </form>

            <!-- ตารางข้อมูล -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>บ้านเลขที่</th>
                        <th>เจ้าของ/ผู้ดูแล</th>
                        <th>โทรศัพท์</th>
                        <th>เนื้อที่ (ตรว)</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // เชื่อมต่อฐานข้อมูล
                    include '../admin/db_connection.php';

                    // กำหนดค่าพื้นฐาน
                    $rows_per_page = isset($_GET['rows']) ? intval($_GET['rows']) : 10;
                    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
                    $offset = $page * $rows_per_page;
                    $house_number = isset($_GET['house_number']) ? $_GET['house_number'] : '';

                    // สร้างคำสั่ง SQL สำหรับการดึงข้อมูล
                    $sql = "SELECT * FROM users WHERE house_number LIKE ? LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        die("Failed to prepare statement: " . $conn->error);
                    }

                    // ป้องกัน SQL Injection
                    $house_number = '%' . $house_number . '%'; // ใช้ '%' เพื่อค้นหาในฐานข้อมูล
                    $stmt->bind_param("sii", $house_number, $rows_per_page, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // ตรวจสอบผลลัพธ์
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['house_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['owner_name']) . "</td>";

                            // ตรวจสอบว่าฟิลด์ contact_number มีข้อมูลหรือไม่
                            echo "<td>" . (isset($row['contact_number']) ? htmlspecialchars($row['contact_number']) : 'ไม่มีข้อมูล') . "</td>";

                            echo "<td>" . htmlspecialchars($row['area']) . "</td>";
                            echo "<td>
                                <a href='../manage_payment/view_history.php?id=" . urlencode($row['id']) . "' class='btn btn-info btn-sm'>ดูประวัติ</a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>ไม่มีข้อมูล</td></tr>";
                    }

                    // ปิดการเชื่อมต่อ
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>

        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>