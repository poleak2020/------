<?php
// เปิดใช้งานการแสดงข้อผิดพลาดสำหรับการดีบัก (ควรปิดใน production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // เริ่มต้น session
require_once '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin
require_once '../admin/db_connection.php'; // เชื่อมต่อฐานข้อมูล

// สร้างฟังก์ชันสำหรับแสดงวันที่เป็น พ.ศ.
function format_thai_date($datetime, $format = "d/m/Y H:i")
{
    $timestamp = strtotime($datetime);
    $year = date("Y", $timestamp) + 543;
    return date("d/m/", $timestamp) . $year . date(" H:i", $timestamp);
}

// รับค่าการค้นหาและหน้าปัจจุบัน
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;

// ตรวจสอบความปลอดภัยของค่า $page และ $limit
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 10;

$offset = ($page - 1) * $limit;

// เตรียมคำสั่ง SQL สำหรับการค้นหาและแบ่งหน้า
$search_param = '%' . $search . '%';

// เนื่องจาก MySQL ไม่รองรับการใช้ bind_param สำหรับ LIMIT และ OFFSET, เราต้องแน่ใจว่า $limit และ $offset เป็นตัวเลขก่อนจึงจะใส่ลงไปใน SQL
$limit = intval($limit);
$offset = intval($offset);

// ใช้ Prepared Statements สำหรับการค้นหา
$sql = "SELECT * FROM announcements WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);

if (!$stmt->execute()) {
    die("การดำเนินการคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($stmt->error));
}

$result = $stmt->get_result();

// ตรวจสอบว่ามีข้อมูลหรือไม่
if ($result === false) {
    die("การดึงข้อมูลล้มเหลว: " . htmlspecialchars($stmt->error));
}

// คำนวณจำนวนหน้าทั้งหมด
$count_sql = "SELECT COUNT(*) FROM announcements WHERE title LIKE ? OR content LIKE ?";
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) {
    die("การเตรียมคำสั่ง SQL สำหรับการนับจำนวนล้มเหลว: " . htmlspecialchars($conn->error));
}
$count_stmt->bind_param("ss", $search_param, $search_param);
if (!$count_stmt->execute()) {
    die("การดำเนินการคำสั่ง SQL สำหรับการนับจำนวนล้มเหลว: " . htmlspecialchars($count_stmt->error));
}
$count_result_row = $count_stmt->get_result()->fetch_row();
$count_result = $count_result_row ? $count_result_row[0] : 0;
$total_pages = ceil($count_result / $limit);

// ปิดการเชื่อมต่อ Statement
$stmt->close();
$count_stmt->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการประชาสัมพันธ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* สไตล์ CSS ตามที่คุณมี */
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px;
            /* พื้นที่สำหรับ navbar fixed-top */
        }

        /* Navbar */
        .navbar {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar */
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 70px;
            /* ความสูงของ navbar */
            left: 0;
            width: 220px;
            background-color: #343a40;
            padding-top: 20px;
            overflow-y: auto;
        }

        .sidebar a {
            color: #fff;
            padding: 10px 20px;
            display: block;
            transition: background 0.3s;
        }

        .sidebar a:hover {
            background-color: #495057;
            text-decoration: none;
        }

        /* Main Content */
        .main-content {
            margin-left: 20px;
            /* ความกว้างของ sidebar */
            padding: 20px;
        }

        /* Card Image */
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        /* Card Hover Effect */
        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: box-shadow 0.3s ease-in-out;
        }

        /* Button Style */
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #4a90e2);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #4a90e2, #007bff);
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #42b72a);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #42b72a, #28a745);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container input {
                width: 100%;
                margin-bottom: 10px;
            }

            .pagination {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">จัดการประชาสัมพันธ์</h1>

            <!-- การแจ้งเตือน (Flash Message) -->
            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
                    htmlspecialchars($_SESSION['message']) .
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                    '</div>';
                unset($_SESSION['message']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                    htmlspecialchars($_SESSION['error']) .
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                    '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <!-- ปุ่มย้อนกลับไปหน้าจัดการหลัก -->
            <a href="../admin/manage.php" class="btn btn-secondary mb-4">
                <i class="fas fa-arrow-left"></i> ย้อนกลับ
            </a>

            <div class="d-flex justify-content-between mb-3 flex-wrap">
                <a href="../manage_announcements/add_announcements.php" class="btn btn-success mb-2">
                    <i class="fas fa-plus"></i> เพิ่มประชาสัมพันธ์ใหม่
                </a>

                <!-- กล่องค้นหา -->
                <form method="GET" class="d-flex mb-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control me-2" placeholder="ค้นหาประกาศ...">
                    <button type="submit" class="btn btn-primary">ค้นหา</button>
                </form>
            </div>

            <h2 class="mb-4">รายการประกาศ (<?= $count_result ?> รายการ)</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $image_path = '../uploads/' . $row['image_url'];
                ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <?php if (!empty($row['image_url']) && file_exists($image_path)): ?>
                                    <a href="<?= htmlspecialchars($image_path) ?>" data-fancybox="gallery" data-caption="รูปภาพสำหรับประกาศ ID: <?= htmlspecialchars($row['id']) ?>">
                                        <img src="<?= htmlspecialchars($image_path) ?>" class="card-img-top" alt="ประกาศ">
                                    </a>
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x200?text=ไม่มีรูปภาพ" class="card-img-top" alt="ไม่มีรูปภาพ">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars(substr($row['content'], 0, 100))) ?>...</p>
                                    <p class="card-text"><small class="text-muted">วันที่: <?= format_thai_date($row['created_at']) ?></small></p>
                                    <div class="mt-auto">
                                        <a href="../manage_announcements/edit_announcement.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-warning btn-sm me-2">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <form method="POST" action="../manage_announcements/delete_announcement.php" class="d-inline" onsubmit="confirmDelete(event)">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash-alt"></i> ลบ
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-info text-center">ไม่มีประกาศ</div></div>';
                }
                ?>
            </div>

            <!-- การแบ่งหน้าแบบ Bootstrap -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <!-- ปุ่ม Previous -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="announcements.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- ลิสต์หน้าต่างๆ -->
                        <?php
                        $range = 2;
                        $start = max(1, $page - $range);
                        $end = min($total_pages, $page + $range);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="announcements.php?page=1&search=' . urlencode($search) . '">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo "<li class='page-item $active'><a class='page-link' href='announcements.php?page=$i&search=" . urlencode($search) . "'>$i</a></li>";
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="announcements.php?page=' . $total_pages . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- ปุ่ม Next -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="announcements.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
    <script>
        function confirmDelete(event) {
            event.preventDefault(); // ป้องกันการส่งฟอร์มทันที
            const form = event.target;

            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "ถ้าลบข้อมูลนี้แล้วไม่สามารถย้อนกลับได้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit(); // ส่งฟอร์มเมื่อยืนยันการลบ
                }
            });
        }
    </script>
</body>

</html>