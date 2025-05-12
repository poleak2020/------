<?php
// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "corporation";

$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตรวจสอบว่าเซสชันมีการกำหนดค่า id หรือไม่
if (isset($_SESSION['user_id'])) {
    $id = intval($_SESSION['user_id']); // กรองให้แน่ใจว่าค่าที่รับมาเป็นตัวเลข

    // ดึงข้อมูลผู้ใช้จากตาราง admin ตาม id ที่เก็บในเซสชัน
    $sql = "SELECT full_name FROM admin WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['full_name'] = $row['full_name']; // ตั้งค่า full_name ในเซสชัน
        } else {
            $_SESSION['full_name'] = 'ไม่พบข้อมูล';
        }

        $stmt->close(); // ปิดคำสั่ง SQL
    } else {
        $_SESSION['full_name'] = 'การเตรียมคำสั่ง SQL ล้มเหลว';
    }
} else {
    $_SESSION['full_name'] = 'ผู้ใช้ไม่ทราบ'; // กำหนดค่าเริ่มต้นหากไม่มี id ในเซสชัน
}

$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <title>CSS Template</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ลิงก์ Bootstrap และไอคอน -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto';
            margin: 0;
        }

        header {
            background-color: #f0f8ff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            color: #5E5E5E;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        nav {
            position: fixed;
            top: 90px;
            left: 0;
            width: 18%;
            height: calc(100% - 100px);
            background: #7BC59D;
            padding: 20px;
            overflow: auto;
            z-index: 1000;
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 0.25rem;
            z-index: 1000;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-toggle::after {
            display: none;
        }

        /* Media queries for responsive design */
        @media (max-width: 992px) {
            nav {
                width: 100%;
                position: relative;
                height: auto;
                padding: 10px;
            }

            header {
                padding: 10px;
            }

            .header-right span {
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            header h2 {
                font-size: 18px;
            }

            .header-right span {
                font-size: 12px;
            }

            nav ul li a {
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            header h2 {
                font-size: 16px;
            }

            nav ul li a {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <header class="d-flex justify-content-between align-items-center">
        <div class="header-left d-flex align-items-center">
            <img src="../img/Logo.png" alt="Logo ของสำนักงานนิติบุคคล" class="me-3" style="height: 50px; width: auto;">
            <h2 class="mb-0">สำนักงานนิติบุคคลหมู่บ้านพฤกษา 28/1</h2>
        </div>

        <div class="header-right d-flex align-items-center gap-3">
            <span>Admin</span>
            <i class="bi bi-person"></i>
            <?php
            // แสดงชื่อเต็มจากเซสชัน
            if (isset($_SESSION['full_name'])) {
                echo '<span>' . htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') . '</span>';
            } else {
                echo '<span>ไม่พบชื่อ</span>';
            }
            ?>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu1">
                    เมนู
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="dropdown-list">
                    <li><a class="dropdown-item" href="../manage_admin/profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="../manage_admin/Settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../admin/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdownMenuButton = document.getElementById("dropdownMenu1");
            const dropdownMenu = document.getElementById("dropdown-list");

            dropdownMenuButton.addEventListener("click", function (event) {
                event.stopPropagation(); // ป้องกันการปิดโดยคลิกที่ปุ่มตัวเอง
                dropdownMenu.classList.toggle("show");
            });

            // คลิกภายนอกเพื่อปิด dropdown
            window.addEventListener("click", function () {
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>
