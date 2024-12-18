<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$page_title = "Agendar Manutenção - Sistema de Manutenção";

// Buscar equipamentos
$stmt = $conn->query("SELECT id, name FROM equipment ORDER BY name");
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar equipes
$stmt = $conn->query("SELECT id, name FROM teams ORDER BY name");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar dados
        if (empty($_POST['equipment_id']) || empty($_POST['team_id']) || 
            empty($_POST['scheduled_date']) || empty($_POST['maintenance_type'])) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        // Preparar dados
        $equipment_id = $_POST['equipment_id'];
        $team_id = $_POST['team_id'];
        $scheduled_date = $_POST['scheduled_date'];
        $maintenance_type = $_POST['maintenance_type'];
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'normal';
        $status = 'scheduled';

        // Inserir manutenção
        $stmt = $conn->prepare("
            INSERT INTO maintenance (
                equipment_id, team_id, scheduled_date, maintenance_type,
                description, priority, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $equipment_id, $team_id, $scheduled_date, $maintenance_type,
            $description, $priority, $status
        ]);

        $_SESSION['success_msg'] = "Manutenção agendada com sucesso!";
        header("Location: maintenance_list.php");
        exit;

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-calendar-plus"></i> Agendar Nova Manutenção
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Equipamento -->
                        <div class="mb-3">
                            <label for="equipment_id" class="form-label">Equipamento</label>
                            <select class="form-select" id="equipment_id" name="equipment_id" required>
                                <option value="">Selecione um equipamento</option>
                                <?php foreach ($equipment as $item): ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um equipamento.
                            </div>
                        </div>

                        <!-- Equipe -->
                        <div class="mb-3">
                            <label for="team_id" class="form-label">Equipe Responsável</label>
                            <select class="form-select" id="team_id" name="team_id" required>
                                <option value="">Selecione uma equipe</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>">
                                        <?php echo htmlspecialchars($team['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione uma equipe.
                            </div>
                        </div>

                        <!-- Data Agendada -->
                        <div class="mb-3">
                            <label for="scheduled_date" class="form-label">Data da Manutenção</label>
                            <input type="datetime-local" class="form-control" id="scheduled_date" 
                                   name="scheduled_date" required>
                            <div class="invalid-feedback">
                                Por favor, selecione uma data.
                            </div>
                        </div>

                        <!-- Tipo de Manutenção -->
                        <div class="mb-3">
                            <label class="form-label">Tipo de Manutenção</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="maintenance_type" 
                                       id="type_preventive" value="preventive" required>
                                <label class="form-check-label" for="type_preventive">
                                    Preventiva
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="maintenance_type" 
                                       id="type_corrective" value="corrective">
                                <label class="form-check-label" for="type_corrective">
                                    Corretiva
                                </label>
                            </div>
                            <div class="invalid-feedback">
                                Por favor, selecione o tipo de manutenção.
                            </div>
                        </div>

                        <!-- Prioridade -->
                        <div class="mb-3">
                            <label for="priority" class="form-label">Prioridade</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">Baixa</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>

                        <!-- Descrição -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" placeholder="Descreva os detalhes da manutenção..."></textarea>
                        </div>

                        <!-- Botões -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-calendar-check"></i> Agendar Manutenção
                            </button>
                            <a href="maintenance_list.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação do formulário
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// Definir data mínima como hoje
document.getElementById('scheduled_date').min = new Date().toISOString().slice(0, 16);
</script>

<?php include 'includes/footer.php'; ?>
