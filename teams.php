<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Verifica se tem permissão de supervisor
if (!checkPermission('supervisor')) {
    header('Location: index.php');
    exit;
}

$page_title = "Equipes - Sistema de Manutenção";

// Adicionar nova equipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $name = $_POST['name'];
    $supervisor_id = $_POST['supervisor_id'];
    $description = $_POST['description'];
    
    $stmt = $conn->prepare("INSERT INTO teams (name, supervisor_id, description) VALUES (?, ?, ?)");
    $stmt->execute([$name, $supervisor_id, $description]);
    
    // Adiciona membros à equipe
    if (isset($_POST['members']) && is_array($_POST['members'])) {
        $team_id = $conn->lastInsertId();
        $stmt = $conn->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
        foreach ($_POST['members'] as $member_id) {
            $stmt->execute([$team_id, $member_id]);
        }
    }
    
    header('Location: teams.php');
    exit;
}

// Buscar supervisores disponíveis
$stmt = $conn->query("SELECT id, username, email FROM users WHERE role IN ('supervisor', 'admin')");
$supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar técnicos disponíveis
$stmt = $conn->query("SELECT id, username, email FROM users WHERE role = 'technician'");
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as equipes
$stmt = $conn->query("
    SELECT t.*, u.username as supervisor_name 
    FROM teams t 
    LEFT JOIN users u ON t.supervisor_id = u.id 
    ORDER BY t.name
");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Equipes</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeamModal">
            Nova Equipe
        </button>
    </div>

    <!-- Lista de Equipes -->
    <div class="row">
        <?php foreach ($teams as $team): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($team['name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($team['supervisor_name']); ?></p>
                        <p><strong>Descrição:</strong> <?php echo htmlspecialchars($team['description']); ?></p>
                        
                        <?php
                        // Buscar membros da equipe
                        $stmt = $conn->prepare("
                            SELECT u.username 
                            FROM team_members tm 
                            JOIN users u ON tm.user_id = u.id 
                            WHERE tm.team_id = ?
                        ");
                        $stmt->execute([$team['id']]);
                        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        ?>
                        
                        <p><strong>Membros:</strong></p>
                        <ul>
                            <?php foreach ($members as $member): ?>
                                <li><?php echo htmlspecialchars($member); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="mt-3">
                            <a href="edit_team.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="view_team_schedule.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-info">Ver Agenda</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Adicionar Equipe -->
<div class="modal fade" id="addTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Equipe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Equipe</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="supervisor_id" class="form-label">Supervisor</label>
                        <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                            <option value="">Selecione um supervisor</option>
                            <?php foreach ($supervisors as $supervisor): ?>
                                <option value="<?php echo $supervisor['id']; ?>">
                                    <?php echo htmlspecialchars($supervisor['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="members" class="form-label">Membros da Equipe</label>
                        <select class="form-select" id="members" name="members[]" multiple required>
                            <?php foreach ($technicians as $technician): ?>
                                <option value="<?php echo $technician['id']; ?>">
                                    <?php echo htmlspecialchars($technician['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_team" class="btn btn-primary">Criar Equipe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#members').select2({
            placeholder: 'Selecione os membros da equipe',
            width: '100%'
        });
    });
</script>
