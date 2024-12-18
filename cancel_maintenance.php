<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id'])) {
    $_SESSION['error_msg'] = "ID da manutenção não fornecido.";
    header("Location: maintenance_list.php");
    exit;
}

try {
    $maintenance_id = $_GET['id'];

    // Verificar se a manutenção existe e está agendada
    $stmt = $conn->prepare("
        SELECT status 
        FROM maintenance 
        WHERE id = ? AND status = 'scheduled'
    ");
    $stmt->execute([$maintenance_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Manutenção não encontrada ou não pode ser cancelada.");
    }

    // Atualizar o status para cancelado
    $stmt = $conn->prepare("
        UPDATE maintenance 
        SET status = 'cancelled', 
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$maintenance_id]);

    $_SESSION['success_msg'] = "Manutenção cancelada com sucesso!";

} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header("Location: maintenance_list.php");
exit;
?>
