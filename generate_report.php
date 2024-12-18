<?php
require_once 'includes/auth.php';
require_once 'includes/MaintenanceReport.php';

// Verificar permissões
if (!checkPermission('supervisor')) {
    $_SESSION['error_msg'] = "Acesso negado. Você precisa ser supervisor ou administrador.";
    header("Location: dashboard.php");
    exit;
}

$page_title = "Gerar Relatório - Sistema de Manutenção";

// Se for uma requisição para gerar PDF
if (isset($_POST['generate_pdf'])) {
    try {
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $status = $_POST['status'] !== 'all' ? $_POST['status'] : null;
        $type = $_POST['type'] !== 'all' ? $_POST['type'] : null;

        // Gerar relatório
        $pdf = MaintenanceReport::generateMaintenanceReport(
            $start_date,
            $end_date,
            $status,
            $type
        );

        // Gerar nome do arquivo
        $filename = 'relatorio_manutencao_' . date('Y-m-d_His') . '.pdf';

        // Enviar PDF para o navegador
        $pdf->Output($filename, 'D');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Erro ao gerar relatório: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
            echo $_SESSION['error_msg'];
            unset($_SESSION['error_msg']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">
                <i class="bi bi-file-pdf"></i> Gerar Relatório de Manutenções
            </h4>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <!-- Período -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all">Todos</option>
                                <option value="scheduled">Agendada</option>
                                <option value="in_progress">Em Andamento</option>
                                <option value="completed">Concluída</option>
                                <option value="cancelled">Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Tipo de Manutenção</label>
                            <select class="form-select" id="type" name="type">
                                <option value="all">Todos</option>
                                <option value="preventive">Preventiva</option>
                                <option value="corrective">Corretiva</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" name="generate_pdf" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> Gerar PDF
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
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

// Configurar data máxima como hoje
document.getElementById('start_date').max = new Date().toISOString().split('T')[0];
document.getElementById('end_date').max = new Date().toISOString().split('T')[0];

// Validar datas
document.getElementById('end_date').addEventListener('change', function() {
    var start = document.getElementById('start_date').value;
    var end = this.value;
    
    if (start && end && start > end) {
        alert('A data final não pode ser anterior à data inicial');
        this.value = '';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
