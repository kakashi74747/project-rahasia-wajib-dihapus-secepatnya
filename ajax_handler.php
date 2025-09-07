<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];
    $pdo = getDbConnection();

    if ($_POST['action'] == 'update_order' && isset($_POST['order'])) {
        $ordered_ids = $_POST['order'];
        
        if (!is_array($ordered_ids) || empty($ordered_ids)) {
            echo json_encode(['success' => false, 'message' => 'Data urutan tidak valid.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE accounts SET display_order = ? WHERE id = ? AND user_id = ?");
            
            foreach ($ordered_ids as $index => $account_id) {
                $stmt->execute([$index, (int)$account_id, $user_id]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui urutan: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] == 'verify_pin' && isset($_POST['pin'], $_POST['account_id'])) {
        $pin = $_POST['pin'];
        $account_id = (int)$_POST['account_id'];

        $stmt_pin = $pdo->prepare("SELECT pin FROM users WHERE id = ?");
        $stmt_pin->execute([$user_id]);
        $user_pin_hash = $stmt_pin->fetchColumn();

        if (password_verify($pin, $user_pin_hash)) {
            $stmt_key = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_key->execute([$user_id]);
            $user_login_hash = $stmt_key->fetchColumn();
            
            define('ENCRYPTION_METHOD', 'aes-256-cbc');
            define('SECRET_KEY', hash('sha256', $user_login_hash));
            define('SECRET_IV', substr(hash('sha256', 'iv-for-'.$user_login_hash), 0, 16));

            function decrypt_password_ajax($string) {
                if (empty($string)) return '';
                return openssl_decrypt(base64_decode($string), ENCRYPTION_METHOD, SECRET_KEY, 0, SECRET_IV);
            }
            
            // PERUBAHAN: Ambil password DAN email
            $stmt_pass = $pdo->prepare("SELECT password, email FROM accounts WHERE id = ? AND user_id = ?");
            $stmt_pass->execute([$account_id, $user_id]);
            $account_data = $stmt_pass->fetch(PDO::FETCH_ASSOC);

            if ($account_data) {
                // PERUBAHAN: Kirim password DAN email dalam response JSON
                echo json_encode([
                    'success' => true, 
                    'password' => decrypt_password_ajax($account_data['password']),
                    'email' => $account_data['email'] 
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Akun tidak ditemukan.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'PIN yang Anda masukkan salah.']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
?>