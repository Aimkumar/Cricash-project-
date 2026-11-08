<?php
session_start();

// 1. डेटाबेस कनेक्शन (Database Connection)
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'cricash_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = "";

// 2. LOGOUT LOGIC
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// 3. LOGIN LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($password, $userData['password'])) {
            $_SESSION['user_id'] = $userData['id'];
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        } else {
            $message = "गलत यूज़रनेम या पासवर्ड!";
        }
    } else {
        $message = "कृपया सभी फील्ड भरें!";
    }
}

// 4. GAME PLAY / BETTING LOGIC (गेम खेलना)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'play_game') {
    if (!isset($_SESSION['user_id'])) {
        die("पहले लॉगिन करें!");
    }

    $userId = $_SESSION['user_id'];
    $betAmount = floatval($_POST['bet_amount'] ?? 0);
    $gameResult = $_POST['game_result'] ?? ''; // 'win' या 'loss'

    if ($betAmount <= 0) {
        $message = "कृपया सही राशि दर्ज करें!";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("यूज़र नहीं मिला!");
            }

            if ($gameResult === 'loss') {
                if ($user['balance'] < $betAmount) {
                    throw new Exception("गेम खेलने के लिए पर्याप्त बैलेंस नहीं है!");
                }
                // हारने पर पैसे कटेंगे (Bug Fixed: $amount ki jagah $betAmount kiya gaya)
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$betAmount, $userId]);
                $message = "आप गेम हार गए! ₹$betAmount कट गए।";
            } elseif ($gameResult === 'win') {
                // जीतने पर पैसे जुड़ेंगे (दुगुनी रकम)
                $winAmount = $betAmount * 2;
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$winAmount, $userId]);
                $message = "बधाई हो! आप गेम जीत गए और ₹$winAmount मिल गए!";
            } else {
                throw new Exception("अमान्य गेम परिणाम!");
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "गेम त्रुटि: " . $e->getMessage();
        }
    }
}

// 5. WITHDRAWAL LOGIC (पैसा निकालना)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'withdraw') {
    if (!isset($_SESSION['user_id'])) {
        die("पहले लॉगिन करें!");
    }

    $userId = $_SESSION['user_id'];
    $withdrawAmount = floatval($_POST['withdraw_amount'] ?? 0);

    if ($withdrawAmount <= 50) {
        $message = "न्यूनतम विथड्रॉल राशि ₹50 होनी चाहिए!";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("यूज़र नहीं मिला!");
            }

            if ($user['balance'] < $withdrawAmount) {
                throw new Exception("विथड्रॉल के लिए आपके खाते में पर्याप्त बैलेंस नहीं है!");
            }

            // खाते से पैसे कम करें
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$withdrawAmount, $userId]);

            $pdo->commit();
            $message = "सफलतापूर्वक ₹$withdrawAmount का विथड्रॉल अनुरोध स्वीकार हो गया है!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "विथड्रॉल विफल: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>Cricash - Game & Withdrawal Portal</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f3; padding: 20px; }
        .box { background: #fff; padding: 20px; max-width: 450px; margin: auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input, select, button { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        button:hover { background: #0056b3; }
        .msg { color: #d9534f; font-weight: bold; margin-bottom: 10px; background: #fdf7f7; padding: 8px; border-left: 4px solid #d9534f; }
        .success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; padding: 8px; margin-bottom: 10px; }
        hr { border: 0; border-top: 1px solid #ddd; margin: 15px 0; }
    </style>
</head>
<body>

<div class="box">
    <h2>🏏 Cricash Dashboard</h2>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo (strpos($message, 'सफलता') !== false || strpos($message, 'बधाई') !== false) ? 'success' : 'msg'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- LOGIN FORM -->
        <h3>यूज़र लॉगिन</h3>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="यूज़रनेम दर्ज करें" required>
            <input type="password" name="password" placeholder="पासवर्ड दर्ज करें" required>
            <button type="submit">Login</button>
        </form>
        <p style="font-size: 12px; color: gray; text-align: center; margin-top: 10px;">
            (नोट: डेटाबेस में यूज़र पहले से बना होना चाहिए और पासवर्ड hashed होना चाहिए।)
        </p>
    <?php 
    else: 
        // यूज़र का ताज़ा बैलेंस लाएं
        $stmt = $pdo->prepare("SELECT username, balance FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    ?>
        <!-- DASHBOARD SECTION -->
        <h3>स्वागत है, <?php echo htmlspecialchars($currentUser['username']); ?>!</h3>
        <p style="font-size: 18px; color: #333;"><strong>करंट बैलेंस:</strong> <span style="color: #28a745;">₹<?php echo number_format($currentUser['balance'], 2); ?></span></p>
        
        <hr>
        
        <!-- GAME PLAY SECTION -->
        <h3>🎮 क्रिकेट गेम खेलें (Bet)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="play_game">
            <input type="number" step="0.01" name="bet_amount" placeholder="दांव लगाने की राशि (Bet Amount)" required>
            <select name="game_result">
                <option value="win">जीत (Win - डबल पैसा पाएं)</option>
                <option value="loss">हार (Loss - पैसा कटेगा)</option>
            </select>
            <button type="submit" style="background: #ffc107; color: #000;">Play Game</button>
        </form>

        <hr>

        <!-- WITHDRAWAL SECTION -->
        <h3>💸 पैसा निकालें (Withdrawal)</h3>
        <form method="POST">
            <input type="hidden" name="action" value="withdraw">
            <input type="number" step="0.01" name="withdraw_amount" placeholder="विथड्रॉल राशि (₹50 से ज़्यादा)" required>
            <button type="submit" style="background: #28a745;">Request Withdrawal</button>
        </form>

        <br>
        <a href="?logout=true"><button style="background: #dc3545;">Logout</button></a>
    <?php endif; ?>
</div>

</body>
</html>

