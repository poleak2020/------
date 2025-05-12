<?php
// ตรวจสอบว่าใช้ HTTPS หรือไม่
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1); // ใช้เฉพาะเมื่อมี HTTPS
}
ini_set('session.cookie_httponly', 1); // ป้องกันการเข้าถึงเซสชันจาก JavaScript
ini_set('session.use_strict_mode', 1); // ป้องกันการใช้ Session ID เก่า

session_start(); // เริ่มต้นเซสชัน

// เชื่อมต่อฐานข้อมูล
include '../admin/db_connection.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตรวจสอบการเข้าสู่ระบบและสร้าง CSRF token
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32)); // สร้าง CSRF token
}

// จำกัดจำนวนครั้งในการพยายามเข้าสู่ระบบ
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_SESSION['login_attempts'] >= 5) {
        $error_message = "คุณพยายามเข้าสู่ระบบหลายครั้งเกินไป กรุณาลองใหม่ภายหลัง";
        sleep(3 * $_SESSION['login_attempts']); // เพิ่มเวลาหน่วงในการล็อกอินใหม่
    } elseif (!hash_equals($_SESSION['token'], $_POST['token'])) {
        $error_message = "CSRF token ไม่ถูกต้อง";
    } else {
        $username = htmlspecialchars(trim($_POST['username']));
        $password = htmlspecialchars(trim($_POST['password']));

        // ตรวจสอบว่าช่องกรอกข้อมูลไม่ว่าง
        if (empty($username) || empty($password)) {
            $error_message = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
        } else {
            // เตรียมคำสั่ง SQL เพื่อป้องกันการโจมตี SQL Injection
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                // ตรวจสอบรหัสผ่านที่เข้ารหัส
                if (password_verify($password, $row['password'])) {
                    // หากข้อมูลถูกต้อง
                    session_regenerate_id(true); // ป้องกันการโจมตีแบบ Session Fixation
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['full_name'] = $row['full_name']; // แทนที่ด้วยข้อมูลจริงจากฐานข้อมูล
                    $_SESSION['login_attempts'] = 0; // รีเซ็ตจำนวนครั้งในการพยายาม

                    // สร้าง CSRF token ใหม่หลังจากล็อกอินสำเร็จ
                    $_SESSION['token'] = bin2hex(random_bytes(32));

                    header("Location: home.php");
                    exit();
                } else {
                    $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                    $_SESSION['login_attempts'] += 1;
                }
            } else {
                $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                $_SESSION['login_attempts'] += 1;
            }

            // ปิดคำสั่ง SQL
            $stmt->close();
        }
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            background-image: url('https://cdn.baania.com/b20/project/main/1602225090347-642959.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            backdrop-filter: blur(8px);
            background-color: rgba(0, 0, 0, 0.6);
        }

        .login-box {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 360px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            margin: 20px;
        }

        .login-title {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
            font-weight: bold;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        label {
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
            text-align: left;
            width: 100%;
        }

        input[type="text"],
        input[type="password"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #00C6FF;
            box-shadow: 0 0 10px rgba(0, 198, 255, 0.5);
        }

        input[type="submit"] {
            padding: 10px 0;
            background: linear-gradient(90deg, #1363FD 0%, #00C6FF 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #00C6FF;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 198, 255, 0.6);
        }

        .message {
            font-size: 14px;
            color: #ff6b6b;
            background-color: #ffe6e6;
            border: 1px solid #ff6b6b;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            animation: fadeInMessage 0.5s ease-in-out;
        }

        @keyframes fadeInMessage {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading Spinner */
        .spinner {
            display: none;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid #00C6FF;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-top: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        footer {
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

        footer p {
            margin: 0;
            color: white;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 576px) {
            .login-box {
                padding: 20px;
                width: 90%;
            }

            .login-title {
                font-size: 20px;
            }

            input[type="text"],
            input[type="password"] {
                font-size: 14px;
            }

            input[type="submit"] {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2 class="login-title">เข้าสู่ระบบ</h2>
        <form method="post" action="" onsubmit="showSpinner()">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>"> <!-- CSRF token -->
            <label for="username">ชื่อผู้ใช้:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">รหัสผ่าน:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="เข้าสู่ระบบ">
        </form>

        <!-- Loading spinner -->
        <div class="spinner" id="loadingSpinner"></div>

        <?php
        if (isset($error_message)) {
            echo "<p class='message'>" . htmlspecialchars($error_message) . "</p>";
        }
        ?>
    </div>

    <footer>
        <p>เว็บไซต์นี้ทั้งหมดได้รับการคุ้มครองลิขสิทธิ์ 2024 - พัฒนาระบบโดยทีมงานวิทยาการคอมพิวเตอร์ (สมุทรปราการ) มหาวิทยาลัยราชภัฏธนบุรี</p>
    </footer>

    <script>
        function showSpinner() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }
    </script>
</body>

</html>
