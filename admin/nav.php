<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เมนู Navigation</title>
    <!-- เชื่อมต่อ Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOMr7AfsMaJbmE23xcuFEmjFEpApk5LghNEFPC" crossorigin="anonymous">
    <!-- CSS สำหรับตกแต่งเมนู -->
    <style>
        /* ตกแต่งเมนู */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        nav {
            background-color: #83C5BE; /* สีพื้นหลังของเมนู */
            padding: 20px;
            width: 200px;
            border-radius: 10px; /* ทำให้มุมโค้งมน */
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* เงาเมนู */
            position: fixed; /* ทำให้เมนูฟิก */
            top: 100px; /* ห่างจากด้านบน */
            left: -220px; /* ซ่อนเมนูไว้ทางซ้าย */
            transition: left 0.3s ease; /* เพิ่มเอฟเฟกต์การเลื่อนนุ่มนวล */
            z-index: 1000; /* ให้อยู่เหนือส่วนอื่น */
        }

        /* ตำแหน่งเมนูเมื่อเปิด */
        nav.active {
            left: 0; /* นำเมนูมาแสดงเมื่อเปิด */
        }

        /* ตกแต่งรายการภายในเมนู */
        nav ul {
            list-style-type: none; /* ซ่อน bullet point */
            padding: 0;
        }

        nav ul li {
            margin: 15px 0; /* เพิ่มระยะห่างระหว่างปุ่ม */
        }

        /* การตกแต่งลิงก์ */
        nav ul li a {
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            color: #2F3E46; /* สีข้อความ */
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease; /* เพิ่มการเปลี่ยนแปลงสีแบบนุ่มนวล */
        }

        /* การจัดตำแหน่งไอคอนและข้อความ */
        nav ul li a i {
            margin-right: 10px; /* ระยะห่างระหว่างไอคอนและข้อความ */
            font-size: 18px;
        }

        /* การตกแต่งเมื่อมีการ hover */
        nav ul li a:hover {
            background-color: #006D77; /* เปลี่ยนสีพื้นหลังเมื่อ hover */
            color: white; /* เปลี่ยนสีข้อความเมื่อ hover */
        }

        /* การตกแต่งเมื่ออยู่ในสถานะ active */
        nav ul li a.active {
            background-color: #028090; /* สีพื้นหลังของปุ่ม active */
            color: white; /* สีข้อความเมื่ออยู่ในสถานะ active */
        }

        /* ปุ่ม Toggle เปิด-ปิดเมนู */
        .menu-toggle {
            display: none; /* ซ่อนปุ่ม toggle */
        }

        /* Media Queries สำหรับหน้าจอขนาดเล็ก */
        @media (max-width: 768px) {
            nav {
                width: 100%;
                left: -100%;
                top: 0;
                height: 100%;
                padding-top: 60px;
            }

            nav.active {
                left: 0;
            }

            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                background-color: #028090;
                color: white;
                padding: 10px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                z-index: 1001;
            }
        }
    </style>
</head>

<body>
    <!-- ปุ่ม Toggle สำหรับเปิด-ปิดเมนู -->
    <button class="menu-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i> เมนู</button>

    <nav id="navMenu">
        <ul>
            <!-- ปุ่มแดชบอร์ด -->
            <li><a href="../admin/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a></li>

            <!-- ปุ่มหน้าหลัก -->
            <li><a href="../admin/home.php" class="<?= basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> หน้าหลัก</a></li>

            <!-- ปุ่มจัดการนิติ -->
            <li><a href="../admin/manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'active' : '' ?>"><i class="fas fa-briefcase"></i> จัดการนิติ</a></li>

            <!-- ปุ่มจัดการผู้ใช้งาน -->
            <li><a href="../admin/users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> จัดการผู้ใช้งาน</a></li>
        </ul>
    </nav>

    <!-- JavaScript สำหรับควบคุมการเปิด-ปิดเมนู -->
    <script>
        function toggleMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        function showMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.add('active'); // เปิดเมนูเมื่อเลื่อนเมาส์เข้ามา
        }

        function hideMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.remove('active'); // ปิดเมนูเมื่อเลื่อนเมาส์ออก
        }

        // ทำให้เมนูแสดงเมื่อเลื่อนเมาส์ไปใกล้ๆ
        document.addEventListener('mousemove', function (event) {
            const navMenu = document.getElementById('navMenu');
            const menuBounding = navMenu.getBoundingClientRect();

            // ตรวจสอบว่ามีการเลื่อนเมาส์ใกล้ๆ
            if (event.clientX < 100 && event.clientY > menuBounding.top && event.clientY < menuBounding.bottom) {
                navMenu.classList.add('active');
            } else if (event.clientX > 200) {
                navMenu.classList.remove('active');
            }
        });
    </script>
</body>

</html>
