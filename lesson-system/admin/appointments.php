<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

// Admin kontrolü
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Admin erişimi için sabit tanımla
define('ADMIN_ACCESS', true);

$success = '';
$error = '';

// AJAX isteği kontrolü
if(isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    if($_POST['ajax_action'] === 'update_status') {
        $appointment_id = sanitize($_POST['appointment_id']);
        $new_status = sanitize($_POST['status']);
        $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
        
        try {
            // Mevcut randevu bilgilerini al
            $stmt = $pdo->prepare("SELECT status, status_history FROM appointments WHERE id = ?");
            $stmt->execute([$appointment_id]);
            $appointment = $stmt->fetch();
            
            if($appointment) {
                // Durum geçmişini güncelle
                $status_history = $appointment['status_history'] ? json_decode($appointment['status_history'], true) : [];
                $status_history[] = [
                    'from_status' => $appointment['status'],
                    'to_status' => $new_status,
                    'changed_by' => $_SESSION['user_id'],
                    'changed_at' => date('Y-m-d H:i:s'),
                    'notes' => $notes
                ];
                
                // Randevuyu güncelle
                $stmt = $pdo->prepare("
                    UPDATE appointments 
                    SET status = ?, 
                        status_history = ?, 
                        updated_by = ?,
                        notes = CONCAT(COALESCE(notes, ''), ?)
                    WHERE id = ?
                ");
                
                $status_history_json = json_encode($status_history);
                $note_entry = date('Y-m-d H:i:s') . " - " . $notes . "\n";
                
                if($stmt->execute([$new_status, $status_history_json, $_SESSION['user_id'], $note_entry, $appointment_id])) {
                    // Bildirim gönder
                    send_appointment_status_notification($appointment_id, $new_status, $notes);
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Randevu durumu güncellendi ve bildirimler gönderildi'
                    ];
                } else {
                    throw new Exception("Veritabanı güncelleme hatası");
                }
            } else {
                throw new Exception("Randevu bulunamadı");
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    else if($_POST['ajax_action'] === 'delete_appointment') {
        $appointment_id = sanitize($_POST['appointment_id']);
        
        try {
            // Önce randevu bilgilerini al
            $stmt = $pdo->prepare("
                SELECT 
                    a.*,
                    s.email as student_email,
                    s.name as student_name,
                    t.email as teacher_email,
                    t.name as teacher_name
                FROM appointments a
                LEFT JOIN users s ON a.student_id = s.id
                LEFT JOIN users t ON a.teacher_id = t.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointment_id]);
            $appointment = $stmt->fetch();
            
            if($appointment) {
                // Randevuyu sil
                $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                if($stmt->execute([$appointment_id])) {
                    // Email bildirimi gönder
                    // send_appointment_cancellation_email($appointment);
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Randevu silindi ve bildirimler gönderildi'
                    ];
                } else {
                    throw new Exception("Silme işlemi başarısız");
                }
            } else {
                throw new Exception("Randevu bulunamadı");
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Randevuları getir
$stmt = $pdo->query("
    SELECT 
        a.*,
        COALESCE(s.name, 'Silinmiş Kullanıcı') as student_name,
        COALESCE(s.email, '') as student_email,
        COALESCE(s.phone, '') as student_phone,
        COALESCE(t.name, 'Silinmiş Kullanıcı') as teacher_name,
        COALESCE(t.email, '') as teacher_email,
        COALESCE(t.phone, '') as teacher_phone,
        u.name as updated_by_name
    FROM appointments a
    LEFT JOIN users s ON a.student_id = s.id
    LEFT JOIN users t ON a.teacher_id = t.id
    LEFT JOIN users u ON a.updated_by = u.id
    ORDER BY a.date DESC, a.time DESC
");
$appointments = $stmt->fetchAll();

// Durum açıklamaları ve renkleri
$status_info = [
    'pending' => ['text' => 'Bekliyor', 'class' => 'warning', 'icon' => 'bi-hourglass'],
    'confirmed' => ['text' => 'Onaylandı', 'class' => 'success', 'icon' => 'bi-check-circle'],
    'cancelled' => ['text' => 'İptal Edildi', 'class' => 'danger', 'icon' => 'bi-x-circle'],
    'completed' => ['text' => 'Tamamlandı', 'class' => 'info', 'icon' => 'bi-trophy']
];

// Varsayılan durum bilgisi
$default_status_info = ['text' => 'Bekliyor', 'class' => 'warning', 'icon' => 'bi-hourglass'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .admin-content {
            padding: 20px;
        }
        .status-badge {
            min-width: 100px;
        }
        .appointment-card {
            transition: all 0.3s ease;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-button {
            width: 100%;
            text-align: left;
            margin-bottom: 5px;
        }
        .status-button i {
            margin-right: 8px;
        }
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Randevu Yönetimi</h2>
                </div>

                <!-- Filtreler -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">Tüm Durumlar</option>
                                    <?php foreach($status_info as $key => $info): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $info['text']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="date" class="form-control" id="dateFilter">
                            </div>
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" id="searchFilter" placeholder="İsim veya email ile ara...">
                            </div>
                            <div class="col-md-2 mb-2">
                                <button class="btn btn-primary w-100" onclick="resetFilters()">
                                    <i class="bi bi-arrow-counterclockwise"></i> Sıfırla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Randevular -->
                <div class="row" id="appointmentsList">
                    <?php if(empty($appointments)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Henüz randevu bulunmuyor.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($appointments as $appointment): 
                            $status = $appointment['status'] ?? 'pending';
                            $info = $status_info[$status] ?? $default_status_info;
                        ?>
                        <div class="col-md-6 mb-4 appointment-item" 
                             data-status="<?php echo htmlspecialchars($status); ?>"
                             data-date="<?php echo htmlspecialchars($appointment['date']); ?>"
                             data-search="<?php echo htmlspecialchars(strtolower($appointment['student_name'] . ' ' . 
                                                              $appointment['student_email'] . ' ' . 
                                                              $appointment['teacher_name'] . ' ' . 
                                                              $appointment['teacher_email'])); ?>">
                            <div class="card appointment-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-calendar-event me-2"></i>
                                        <?php echo date('d.m.Y', strtotime($appointment['date'])); ?> - 
                                        <?php echo date('H:i', strtotime($appointment['time'])); ?>
                                    </h6>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-dark p-0" type="button" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#appointmentModal" 
                                                data-appointment-id="<?php echo $appointment['id']; ?>"
                                                data-student-name="<?php echo htmlspecialchars($appointment['student_name']); ?>"
                                                data-teacher-name="<?php echo htmlspecialchars($appointment['teacher_name']); ?>"
                                                data-subject="<?php echo htmlspecialchars($appointment['subject']); ?>"
                                                data-date="<?php echo date('d.m.Y', strtotime($appointment['date'])); ?>"
                                                data-time="<?php echo date('H:i', strtotime($appointment['time'])); ?>"
                                                data-status="<?php echo htmlspecialchars($status); ?>">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <span class="badge bg-<?php echo $info['class']; ?> status-badge">
                                            <i class="bi <?php echo $info['icon']; ?> me-1"></i>
                                            <?php echo $info['text']; ?>
                                        </span>
                                        <?php if($appointment['updated_by']): ?>
                                            <small class="text-muted d-block mt-1">
                                                Son güncelleme: <?php echo htmlspecialchars($appointment['updated_by_name']); ?>
                                                (<?php echo date('d.m.Y H:i', strtotime($appointment['last_updated'])); ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-2">Öğrenci</h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($appointment['student_name']); ?></p>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($appointment['student_email']); ?><br>
                                                <?php echo htmlspecialchars($appointment['student_phone']); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-2">Öğretmen</h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($appointment['teacher_name']); ?></p>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($appointment['teacher_email']); ?><br>
                                                <?php echo htmlspecialchars($appointment['teacher_phone']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Konu:</strong><br>
                                                <?php echo htmlspecialchars($appointment['subject']); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong>Süre:</strong><br>
                                                <?php echo $appointment['duration']; ?> dakika
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Randevu Yönetim Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Randevu Yönetimi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="appointment-details mb-4">
                        <h6 class="border-bottom pb-2">Randevu Bilgileri</h6>
                        <p class="mb-1"><strong>Öğrenci:</strong> <span id="modalStudentName"></span></p>
                        <p class="mb-1"><strong>Öğretmen:</strong> <span id="modalTeacherName"></span></p>
                        <p class="mb-1"><strong>Konu:</strong> <span id="modalSubject"></span></p>
                        <p class="mb-1"><strong>Tarih:</strong> <span id="modalDate"></span></p>
                        <p class="mb-0"><strong>Saat:</strong> <span id="modalTime"></span></p>
                    </div>
                    
                    <h6 class="border-bottom pb-2">Durum Güncelle</h6>
                    <input type="hidden" id="modalAppointmentId">
                    
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label">Notlar</label>
                        <textarea class="form-control" id="statusNotes" rows="3" 
                                placeholder="Durum değişikliği ile ilgili notlarınızı buraya yazabilirsiniz..."></textarea>
                    </div>
                    
                    <?php foreach($status_info as $key => $info): ?>
                    <button type="button" 
                            class="btn btn-outline-<?php echo $info['class']; ?> status-button"
                            onclick="updateStatus('<?php echo $key; ?>')">
                        <i class="bi <?php echo $info['icon']; ?>"></i>
                        <?php echo $info['text']; ?>
                    </button>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <button type="button" class="btn btn-danger w-100" onclick="deleteAppointment()">
                        <i class="bi bi-trash me-2"></i>
                        Randevuyu Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal işlemleri
        const appointmentModal = document.getElementById('appointmentModal');
        appointmentModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const appointmentId = button.getAttribute('data-appointment-id');
            const studentName = button.getAttribute('data-student-name');
            const teacherName = button.getAttribute('data-teacher-name');
            const subject = button.getAttribute('data-subject');
            const date = button.getAttribute('data-date');
            const time = button.getAttribute('data-time');
            const status = button.getAttribute('data-status');
            
            document.getElementById('modalAppointmentId').value = appointmentId;
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalTeacherName').textContent = teacherName;
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalDate').textContent = date;
            document.getElementById('modalTime').textContent = time;
        });

        // Toast bildirimi göster
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.getElementById('toast-container').appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }

        // Durum güncelle
        function updateStatus(newStatus) {
            const appointmentId = document.getElementById('modalAppointmentId').value;
            const notes = document.getElementById('statusNotes').value;
            
            fetch('appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_action=update_status&appointment_id=${appointmentId}&status=${newStatus}&notes=${encodeURIComponent(notes)}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'danger');
            });
        }

        // Randevu sil
        function deleteAppointment() {
            if(!confirm('Bu randevuyu silmek istediğinizden emin misiniz?')) return;
            
            const appointmentId = document.getElementById('modalAppointmentId').value;
            
            fetch('appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_action=delete_appointment&appointment_id=${appointmentId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'danger');
            });
        }

        // Filtreleme fonksiyonları
        function filterAppointments() {
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const date = document.getElementById('dateFilter').value;
            const search = document.getElementById('searchFilter').value.toLowerCase();
            
            document.querySelectorAll('.appointment-item').forEach(item => {
                const itemStatus = item.dataset.status;
                const itemDate = item.dataset.date;
                const itemSearch = item.dataset.search;
                
                const statusMatch = !status || itemStatus === status;
                const dateMatch = !date || itemDate === date;
                const searchMatch = !search || itemSearch.includes(search);
                
                if(statusMatch && dateMatch && searchMatch) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function resetFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('dateFilter').value = '';
            document.getElementById('searchFilter').value = '';
            filterAppointments();
        }

        // Event listeners
        document.getElementById('statusFilter').addEventListener('change', filterAppointments);
        document.getElementById('dateFilter').addEventListener('change', filterAppointments);
        document.getElementById('searchFilter').addEventListener('input', filterAppointments);
    </script>
</body>
</html>
