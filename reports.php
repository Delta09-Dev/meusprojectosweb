<?php
require_once 'includes/auth.php';
require_once 'includes/MaintenanceReport.php';

// Verificar permissões
if (!checkPermission('supervisor')) {
    $_SESSION['error_msg'] = "Acesso negado. Você precisa ser supervisor ou administrador.";
    header("Location: dashboard.php");
    exit;
}

$page_title = "Relatórios - Sistema de Manutenção";

// Se for uma requisição para gerar PDF
if (isset($_GET['generate_pdf'])) {
    try {
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;

        // Gerar relatório completo
        $pdf = MaintenanceReport::generateFullReport($start_date, $end_date);

        // Gerar nome do arquivo
        $filename = 'relatorio_sistema_' . date('Y-m-d_His') . '.pdf';

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
                <i class="bi bi-file-text"></i> Relatórios do Sistema
            </h4>
        </div>
        <div class="card-body">
            <!-- Relatório de Manutenções -->
            <div class="mb-4">
                <h5><i class="bi bi-tools"></i> Relatório de Manutenções</h5>
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Data Inicial</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Data Final</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                    <button type="submit" name="generate_pdf" class="btn btn-danger">
                                        <i class="bi bi-file-pdf"></i> Exportar PDF
                                    </button>
                                </div>
                            </div>
                        </form>
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

                <!-- Botões -->
                <div class="col-12">
                    <button type="submit" name="generate_pdf" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> Gerar PDF
                    </button>
                </div>
            </div>

            <hr>

            <!-- Outros tipos de relatórios podem ser adicionados aqui -->
            <div class="text-muted">
                <p><i class="bi bi-info-circle"></i> Mais tipos de relatórios serão adicionados em breve.</p>
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
