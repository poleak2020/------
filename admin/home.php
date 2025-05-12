<?php
// เปิดการแสดงข้อผิดพลาด (สำหรับการดีบัก)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// รวมไฟล์ตรวจสอบการล็อกอินและเชื่อมต่อฐานข้อมูลเพียงครั้งเดียว
include_once '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
include_once '../admin/db_connection.php'; // เชื่อมต่อฐานข้อมูลเพียงครั้งเดียว

// ดึงจำนวนบ้านจากตาราง users
$sql_count = "SELECT COUNT(*) AS total FROM users";
$result_count = $conn->query($sql_count);

if ($result_count) {
    $row_count = $result_count->fetch_assoc();
    $total_houses = htmlspecialchars($row_count['total']); // จำนวนบ้านทั้งหมด
} else {
    die("การดึงข้อมูลจำนวนบ้านล้มเหลว: " . $conn->error);
}

// ฟังก์ชันแปลงเดือนและปีเป็นภาษาไทย
function formatThaiMonthYear($month, $year)
{
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    $thaiYear = $year; // หากข้อมูลปีเป็น พ.ศ. ไม่ต้องเพิ่ม 543
    $thaiMonth = isset($months[intval($month)]) ? $months[intval($month)] : 'ไม่ทราบเดือน';

    return "$thaiMonth $thaiYear";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <title>ประวัติการชำระเงิน</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            background-color: #f0f2f5;
        }

        article {
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            margin-top: 100px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            max-width: 1200px;
        }

        h1 {
            background-color: #83C5BE;
            color: white;
            padding: 15px;
            font-size: 28px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .box-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .box1 {
            background-color: #f8f9fa;
            flex: 1;
            margin: 10px;
            padding: 20px;
            text-align: center;
            color: #343a40;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .table-container {
            display: flex;
            justify-content: center;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .table {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .rows-per-page {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .rows-per-page label {
            margin-right: 10px;
        }

        .input-group {
            max-width: 300px;
            width: 100%;
            margin-bottom: 15px;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            transition: background-color 0.3s ease;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        @media (max-width: 768px) {
            .box-container {
                flex-direction: column;
            }

            h1 {
                font-size: 22px;
            }

            .search-form {
                flex-direction: column;
            }

            .input-group {
                margin-bottom: 10px;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            article {
                padding: 15px;
            }

            .box-container {
                margin: 0;
            }

            h1 {
                font-size: 20px;
            }

            .input-group {
                margin-bottom: 10px;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include '../admin/header.php'; ?>
    <?php include '../admin/nav.php'; ?>

    <article>
        <h1><i class="fas fa-home"></i> สำนักงานนิติบุคคลหมู่บ้านพฤกษา 28/1</h1>
        <div class="box-container">
            <div class="box1">
                <p><i class="fas fa-map-marker-alt"></i> ที่อยู่: 189/429 หมู่ที่ 6 ถนนลำบางผี<br>
                    ตำบลแพรกษา อำเภอเมืองสมุทรปราการ<br>
                    จังหวัดสมุทรปราการ 10280</p>
            </div>
            <div class="box1">
                <p><i class="fas fa-clock"></i> เวลาทำการ: 10.00 น. - 19.00 น.<br>
                    หยุดทุกวันอังคารและวันหยุดนักขัตฤกษ์</p>
            </div>
            <div class="box1">
                <p><i class="fas fa-phone"></i> ติดต่อสอบถาม: 095 - 5868488, 02-1865217<br>
                    E-mail: niti2pruksa28_1@hotmail.com</p>
            </div>
        </div>  

        <h1>จำนวนบ้านทั้งหมด (<?php echo $total_houses; ?>) หลังคาเรือน</h1>
        <form method="GET" action="" class="search-form">
            <div class="rows-per-page">
                <label for="rows">แสดง:</label>
                <select id="rows" name="rows" class="form-select" style="width: auto;">
                    <option value="1" <?php if (isset($_GET['rows']) && $_GET['rows'] == 1) echo 'selected'; ?>>1</option>
                    <option value="5" <?php if (isset($_GET['rows']) && $_GET['rows'] == 5) echo 'selected'; ?>>5</option>
                    <option value="10" <?php if (isset($_GET['rows']) && $_GET['rows'] == 10) echo 'selected'; ?>>10</option>
                    <option value="0" <?php if (isset($_GET['rows']) && $_GET['rows'] == 0) echo 'selected'; ?>>แสดงทั้งหมด</option>
                </select>
            </div>
            <div class="input-group">
                <input type="text" class="form-control" placeholder="กรอกเลขที่บ้าน..." name="house_number" value="<?php echo isset($_GET['house_number']) ? htmlspecialchars($_GET['house_number']) : ''; ?>">
                <button class="btn btn-success" type="submit"><i class="fas fa-search"></i> ค้นหา</button>
                <a href="home.php" class="btn btn-secondary">ล้างค่าการค้นหา</a>
            </div>
        </form>


        <div class="table-container">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ลำดับ</th>
                        <th scope="col">บ้านเลขที่</th>
                        <th scope="col">เจ้าบ้าน/ผู้ดูแล</th>
                        <th scope="col">โทรศัพท์</th>
                        <th scope="col">ชำระส่วนกลางล่าสุด</th>
                        <th scope="col">ค้างชำระ</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    include '../admin/db_connection.php';

                    // ตรวจสอบว่าการเชื่อมต่อยังเปิดอยู่
                    if (!$conn->ping()) {
                        die("การเชื่อมต่อฐานข้อมูลถูกปิดแล้ว");
                    }

                    $rows_per_page = isset($_GET['rows']) ? intval($_GET['rows']) : 1;
                    $allowed_rows = [1, 5, 10, 0];
                    if (!in_array($rows_per_page, $allowed_rows)) {
                        $rows_per_page = 1;
                    }

                    $house_number = isset($_GET['house_number']) ? preg_replace('/[^a-zA-Z0-9\-\/]/', '', $_GET['house_number']) : '';

                    if ($rows_per_page == 0) {
                        $limit_clause = "";
                    } else {
                        $limit_clause = "LIMIT ?";
                    }

                    $sql = "
                        SELECT 
                            u.id, 
                            u.house_number, 
                            u.owner_name, 
                            u.contact_number AS phone, 
                            MAX(p.end_month) AS last_payment_month,
                            MAX(p.payment_year) AS last_payment_year,
                            MAX(p.payment_date) AS last_payment_date
                        FROM 
                            users u
                        LEFT JOIN 
                            payments p 
                        ON 
                            u.house_number = p.house_number
                        WHERE u.house_number LIKE ?
                        GROUP BY u.id 
                        ORDER BY u.house_number 
                        $limit_clause";

                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        die("การเตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
                    }

                    $house_number_param = "%" . $house_number . "%";

                    if ($rows_per_page == 0) {
                        $stmt->bind_param("s", $house_number_param);
                    } else {
                        $stmt->bind_param("si", $house_number_param, $rows_per_page);
                    }

                    if (!$stmt->execute()) {
                        die("การดึงข้อมูลล้มเหลว: " . $stmt->error);
                    }

                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $index = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<th scope='row'>" . $index++ . "</th>";
                            echo "<td>" . htmlspecialchars($row['house_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['owner_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";

                            $last_payment_month = $row['last_payment_month'];
                            $last_payment_year = $row['last_payment_year'];

                            if ($last_payment_month && $last_payment_year) {
                                $formatted_date = formatThaiMonthYear($last_payment_month, $last_payment_year);
                            } else {
                                $formatted_date = 'ไม่มีข้อมูล';
                            }
                            echo "<td>" . htmlspecialchars($formatted_date) . "</td>";

                            // คำนวณจำนวนเดือนที่ค้างชำระ
                            if ($last_payment_month && $last_payment_year) {
                                $current_year_pth = date('Y') + 543;
                                $current_month = date('n');

                                $months_overdue = ($current_year_pth - $last_payment_year) * 12 + ($current_month - intval($last_payment_month));

                                if ($months_overdue > 0) {
                                    $arrears = $months_overdue . " เดือน";
                                } else {
                                    $arrears = "ไม่มีค้างชำระ";
                                }
                            } else {
                                $arrears = 'ไม่มีข้อมูล';
                            }

                            echo "<td>" . htmlspecialchars($arrears) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>ไม่พบข้อมูล</td></tr>";
                    }

                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </article>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>