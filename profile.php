<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

// Busca informações do usuário
$stmt = $conn->prepare("
    SELECT u.*, t.name as team_name
    FROM users u
    LEFT JOIN team_members tm ON u.id = tm.user_id
    LEFT JOIN teams t ON tm.team_id = t.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca histórico de manutenções
$stmt = $conn->prepare("
    SELECT 
        ms.scheduled_date,
        e.name as equipment_name,
        ms.maintenance_type,
        ms.status,
        mr.findings,
        mr.actions_taken
    FROM maintenance_schedule ms
    JOIN equipment e ON ms.equipment_id = e.id
    JOIN team_members tm ON ms.team_id = tm.team_id
    LEFT JOIN maintenance_reports mr ON ms.id = mr.maintenance_id
    WHERE tm.user_id = ?
    ORDER BY ms.scheduled_date DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$maintenance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processa atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;
    
    // Verifica senha atual
    if (!empty($_POST['current_password'])) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($_POST['current_password'], $current_user['password'])) {
            $updates = [];
            $params = [];
            
            // Atualiza email
            if (!empty($_POST['email']) && $_POST['email'] !== $user['email']) {
                $updates[] = "email = ?";
                $params[] = $_POST['email'];
            }
            
            // Atualiza senha
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $updates[] = "password = ?";
                    $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                } else {
                    $error = "As senhas não conferem";
                }
            }
            
            if (!empty($updates) && !$error) {
                $params[] = $user_id;
                $stmt = $conn->prepare("UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?");
                if ($stmt->execute($params)) {
                    $success = "Perfil atualizado com sucesso!";
                    // Atualiza dados do usuário
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        } else {
            $error = "Senha atual incorreta";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Sistema de Manutenção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Perfil do Usuário -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                        <?php if ($user['team_name']): ?>
                            <p><i class="bi bi-people"></i> <?php echo htmlspecialchars($user['team_name']); ?></p>
                        <?php endif; ?>
                        <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <!-- Formulário de Atualização -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Atualizar Perfil</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            <button type="submit" class="btn btn-primary">Atualizar</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Histórico de Manutenções -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Histórico de Manutenções</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($maintenance_history): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Equipamento</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenance_history as $maintenance): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($maintenance['scheduled_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($maintenance['equipment_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $maintenance['maintenance_type'] == 'preventive' ? 'primary' : 'warning'; ?>">
                                                        <?php echo $maintenance['maintenance_type']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $maintenance['status'] == 'completed' ? 'success' : 
                                                            ($maintenance['status'] == 'scheduled' ? 'info' : 'secondary');
                                                    ?>">
                                                        <?php echo $maintenance['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($maintenance['findings']): ?>
                                                        <button type="button" class="btn btn-sm btn-info" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#detailsModal"
                                                                data-findings="<?php echo htmlspecialchars($maintenance['findings']); ?>"
                                                                data-actions="<?php echo htmlspecialchars($maintenance['actions_taken']); ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>Nenhuma manutenção registrada.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Manutenção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Constatações</h6>
                    <p id="modalFindings"></p>
                    
                    <h6>Ações Realizadas</h6>
                    <p id="modalActions"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preenche o modal de detalhes
        document.getElementById('detailsModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var findings = button.getAttribute('data-findings');
            var actions = button.getAttribute('data-actions');
            
            document.getElementById('modalFindings').textContent = findings;
            document.getElementById('modalActions').textContent = actions;
        });
    </script>
</body>
</html>
