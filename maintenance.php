<?php
session_start();
require_once 'config/database.php';

// Add new maintenance schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_maintenance'])) {
    $equipment_id = $_POST['equipment_id'];
    $team_id = $_POST['team_id'];
    $scheduled_date = $_POST['scheduled_date'];
    $maintenance_type = $_POST['maintenance_type'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO maintenance_schedule (equipment_id, team_id, scheduled_date, maintenance_type, description) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$equipment_id, $team_id, $scheduled_date, $maintenance_type, $description]);

    // Create email alert for the team
    $stmt = $conn->prepare("INSERT INTO email_alerts (type, recipient_id, message) 
                           SELECT 'maintenance_due', user_id, CONCAT('Nova manutenção agendada para ', ?) 
                           FROM team_members WHERE team_id = ?");
    $stmt->execute([$scheduled_date, $team_id]);
}

// Fetch equipment for dropdown
$stmt = $conn->query("SELECT id, name FROM equipment ORDER BY name");
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teams for dropdown
$stmt = $conn->query("SELECT id, name FROM teams ORDER BY name");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch scheduled maintenance
$stmt = $conn->query("SELECT ms.*, e.name as equipment_name, t.name as team_name 
                      FROM maintenance_schedule ms 
                      JOIN equipment e ON ms.equipment_id = e.id 
                      JOIN teams t ON ms.team_id = t.id 
                      ORDER BY scheduled_date");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Manutenções</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Gestão de Manutenções</h2>
        
        <!-- Schedule Maintenance Form -->
        <div class="card mb-4">
            <div class="card-header">
                Agendar Nova Manutenção
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="equipment_id" class="form-label">Equipamento</label>
                            <select class="form-select" id="equipment_id" name="equipment_id" required>
                                <option value="">Selecione o equipamento</option>
                                <?php foreach ($equipment as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="team_id" class="form-label">Equipa</label>
                            <select class="form-select" id="team_id" name="team_id" required>
                                <option value="">Selecione a equipa</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="scheduled_date" class="form-label">Data Agendada</label>
                            <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="maintenance_type" class="form-label">Tipo de Manutenção</label>
                            <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                <option value="preventive">Preventiva</option>
                                <option value="corrective">Corretiva</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="schedule_maintenance" class="btn btn-primary">Agendar Manutenção</button>
                </form>
            </div>
        </div>

        <!-- Maintenance Calendar -->
        <div class="card mb-4">
            <div class="card-header">
                Calendário de Manutenções
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Maintenance List -->
        <div class="card">
            <div class="card-header">
                Lista de Manutenções Agendadas
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Equipamento</th>
                            <th>Equipa</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['team_name']); ?></td>
                            <td><?php echo $schedule['scheduled_date']; ?></td>
                            <td><?php echo $schedule['maintenance_type']; ?></td>
                            <td><?php echo $schedule['status']; ?></td>
                            <td>
                                <a href="edit_maintenance.php?id=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="complete_maintenance.php?id=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-success">Concluir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt',
                events: [
                    <?php foreach ($schedules as $schedule): ?>
                    {
                        title: '<?php echo $schedule['equipment_name']; ?> - <?php echo $schedule['team_name']; ?>',
                        start: '<?php echo $schedule['scheduled_date']; ?>',
                        color: '<?php echo ($schedule['maintenance_type'] == 'preventive') ? '#28a745' : '#dc3545'; ?>'
                    },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>
