<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Takvim - Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-4">Randevu Takvimi</h3>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Randevu Detay Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Randevu Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="appointmentDetails"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/tr.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'tr',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'get-appointments.php',
                eventClick: function(info) {
                    showAppointmentDetails(info.event.id);
                }
            });
            calendar.render();
        });

        function showAppointmentDetails(appointmentId) {
            fetch('get-appointment-details.php?id=' + appointmentId)
                .then(response => response.json())
                .then(data => {
                    let details = `
                        <p><strong>Öğretmen:</strong> ${data.teacher_name}</p>
                        <p><strong>Öğrenci:</strong> ${data.student_name}</p>
                        <p><strong>Ders:</strong> ${data.subject}</p>
                        <p><strong>Tarih:</strong> ${data.date}</p>
                        <p><strong>Saat:</strong> ${data.time}</p>
                        <p><strong>Süre:</strong> ${data.duration} dakika</p>
                        <p><strong>Durum:</strong> ${data.status}</p>
                    `;
                    document.getElementById('appointmentDetails').innerHTML = details;
                    new bootstrap.Modal(document.getElementById('appointmentModal')).show();
                });
        }
    </script>
</body>
</html>
