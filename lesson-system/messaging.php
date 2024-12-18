<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
checkLogin();

// Mesaj gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = sanitize($_POST['message']);
    
    $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
    
    header("Location: messaging.php?user=" . $receiver_id);
    exit();
}

// Mesajlaşılan kullanıcıyı al
$other_user_id = $_GET['user'] ?? null;
if ($other_user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlar - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .messages-container {
            height: 400px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
        }
        .message-sent {
            background-color: #007bff;
            color: white;
            margin-left: 20%;
        }
        .message-received {
            background-color: #e9ecef;
            margin-right: 20%;
        }
        .contacts-list {
            height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Kişiler</h5>
                    </div>
                    <div class="card-body contacts-list">
                        <?php
                        // Mesajlaşılan kişileri getir
                        $sql = "SELECT DISTINCT u.* 
                                FROM users u 
                                JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
                                WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
                                ORDER BY u.name";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                        $contacts = $stmt->fetchAll();

                        foreach ($contacts as $contact) {
                            $active = ($other_user_id == $contact['id']) ? 'active bg-primary text-white' : '';
                            echo "<a href='messaging.php?user={$contact['id']}' class='list-group-item list-group-item-action {$active}'>";
                            echo htmlspecialchars($contact['name']);
                            echo "</a>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php echo $other_user ? htmlspecialchars($other_user['name']) : 'Mesajlar'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($other_user): ?>
                            <div class="messages-container mb-3">
                                <?php
                                $sql = "SELECT * FROM messages 
                                        WHERE (sender_id = ? AND receiver_id = ?) 
                                        OR (sender_id = ? AND receiver_id = ?)
                                        ORDER BY created_at ASC";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$_SESSION['user_id'], $other_user_id, $other_user_id, $_SESSION['user_id']]);
                                $messages = $stmt->fetchAll();

                                foreach ($messages as $message) {
                                    $class = ($message['sender_id'] == $_SESSION['user_id']) ? 'message-sent' : 'message-received';
                                    echo "<div class='message {$class}'>";
                                    echo htmlspecialchars($message['message']);
                                    echo "<small class='d-block text-muted'>" . date('d.m.Y H:i', strtotime($message['created_at'])) . "</small>";
                                    echo "</div>";
                                }
                                ?>
                            </div>
                            <form method="POST" action="">
                                <div class="input-group">
                                    <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                                    <input type="text" name="message" class="form-control" placeholder="Mesajınızı yazın..." required>
                                    <button type="submit" class="btn btn-primary">Gönder</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <p class="text-center">Mesajlaşmak için sol taraftan bir kişi seçin.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mesajları en alta kaydır
        var messagesContainer = document.querySelector('.messages-container');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Her 5 saniyede bir yeni mesajları kontrol et
        setInterval(function() {
            if (window.location.search.includes('user=')) {
                location.reload();
            }
        }, 5000);
    </script>
</body>
</html>
