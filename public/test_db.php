<?php
// public/test_db.php

// Inclui o arquivo de configuração para ter acesso às constantes do DB
// O caminho '../src/config.php' assume que 'public' e 'src' estão no mesmo nível.
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Core/Database.php';

echo "Tentando conectar ao banco de dados...<br>";

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    if ($conn) {
        echo "Conexão com o banco de dados realizada com sucesso!<br>";

        // Teste simples: Tentar buscar algo da tabela de usuários
        // (Esta parte pode dar erro se a tabela 'users' ainda não tiver sido criada no DB online)
        try {
            $stmt = $conn->query("SELECT id, name FROM users LIMIT 1");
            $user = $stmt->fetch();

            if ($user) {
                echo "Dados de usuário de teste encontrados: ID = " . $user['id'] . ", Nome = " . $user['name'] . "<br>";
            } else {
                echo "Nenhum usuário encontrado na tabela 'users'. (Isso é normal se você acabou de criar o DB)<br>";
            }
        } catch (PDOException $e) {
            echo "Erro ao consultar a tabela 'users': " . $e->getMessage() . "<br>";
            echo "Isso pode significar que a tabela 'users' ainda não foi criada no banco de dados online.<br>";
        }

    } else {
        echo "Falha na obtenção da conexão com o banco de dados.";
    }
} catch (PDOException $e) {
    echo "Erro de Conexão PDO: " . $e->getMessage() . "<br>";
    echo "Verifique as credenciais no src/config.php e as permissões de acesso remoto no seu provedor de hospedagem.<br>";
    echo "Certifique-se também de que o servidor do banco de dados online está ativo.<br>";
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage() . "<br>";
}
?>