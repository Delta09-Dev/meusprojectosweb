<?php
require_once 'config/database.php';

// Dados do administrador
$admin_user = [
    'username' => 'admin',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'email' => 'admin@empresa.com',
    'role' => 'admin'
];

try {
    // Verifica se já existe um admin
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$admin_user['username']]);
    
    if (!$stmt->fetch()) {
        // Insere o usuário admin
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([
            $admin_user['username'],
            $admin_user['password'],
            $admin_user['email'],
            $admin_user['role']
        ])) {
            echo "<div style='color: green; margin: 20px;'>";
            echo "Usuário administrador criado com sucesso!<br>";
            echo "Username: admin<br>";
            echo "Senha: admin123<br>";
            echo "Email: admin@empresa.com<br>";
            echo "</div>";
            
            // Debug - mostrar o hash gerado
            echo "<div style='color: blue; margin: 20px;'>";
            echo "Hash da senha gerado: " . $admin_user['password'];
            echo "</div>";
        } else {
            echo "<div style='color: red; margin: 20px;'>";
            echo "Erro ao inserir usuário administrador.";
            echo "</div>";
        }
    } else {
        // Atualiza a senha do admin existente
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        if ($stmt->execute([$admin_user['password'], $admin_user['username']])) {
            echo "<div style='color: green; margin: 20px;'>";
            echo "Senha do administrador atualizada com sucesso!<br>";
            echo "Username: admin<br>";
            echo "Nova senha: admin123<br>";
            echo "</div>";
            
            // Debug - mostrar o hash gerado
            echo "<div style='color: blue; margin: 20px;'>";
            echo "Novo hash da senha: " . $admin_user['password'];
            echo "</div>";
        } else {
            echo "<div style='color: red; margin: 20px;'>";
            echo "Erro ao atualizar a senha do administrador.";
            echo "</div>";
        }
    }
} catch(PDOException $e) {
    echo "<div style='color: red; margin: 20px;'>";
    echo "Erro ao criar/atualizar usuário administrador: " . $e->getMessage();
    echo "</div>";
}
?>
