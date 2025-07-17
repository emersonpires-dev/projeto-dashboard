<?php
// src/Core/Database.php

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        // Inclui o arquivo de configuração para ter acesso às constantes do DB
        require_once __DIR__ . '/../config.php';

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna os resultados como array associativo
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em um ambiente de produção, você registraria o erro
            // e exibiria uma mensagem genérica para o usuário.
            die('Erro de Conexão com o Banco de Dados: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>