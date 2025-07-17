<?php
// src/Models/User.php

require_once __DIR__ . '/../Core/Database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password_hash; // Esta propriedade será usada para o hash do DB
    public $user_type;
    public $created_at;

    public $password_input; // Propriedade para a senha bruta digitada no formulário (input)

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    // Método para registrar um novo usuário
    public function register() {
        // Verifica se o email já existe
        if ($this->emailExists()) {
            return false; // Email já cadastrado
        }

        // Hash da senha para segurança (usa password_input para a senha bruta)
        $this->password_hash = password_hash($this->password_input, PASSWORD_BCRYPT);

        // Query para inserir o usuário
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    name = :name,
                    email = :email,
                    password_hash = :password_hash,
                    user_type = :user_type";

        // Prepara a declaração
        $stmt = $this->conn->prepare($query);

        // Sanitiza os dados (remove tags HTML e converte entidades)
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));

        // Vincula os valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':user_type', $this->user_type);

        // Executa a query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Método para verificar se um email já existe E popula as propriedades do usuário
    public function emailExists() {
        $query = "SELECT id, name, password_hash, user_type
                FROM " . $this->table_name . "
                WHERE email = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email)); // Sanitiza o email antes de vincular
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password_hash = $row['password_hash']; // Popula a propriedade da classe com o HASH do DB
            $this->user_type = $row['user_type'];
            return true;
        }
        return false;
    }

    // Método para login
    public function login() {
        if ($this->emailExists()) {
            if (password_verify($this->password_input, $this->password_hash)) {
                return true;
            }
        }
        return false;
    }

    // Método para buscar todos os usuários
    public function getAllUsers() {
        $query = "SELECT id, name, email, user_type, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt; // Retorna o objeto PDOStatement
    }

    // Método para buscar um único usuário pelo ID
    public function getOneUser() {
        $query = "SELECT id, name, email, user_type FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id); // Vincula o ID passado à propriedade
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->user_type = $row['user_type'];
            return true;
        }
        return false;
    }

    // Método para atualizar um usuário existente
    public function update() {
        // Inicia a query com os campos que sempre serão atualizados
        $query = "UPDATE " . $this->table_name . "
                SET
                    name = :name,
                    email = :email,
                    user_type = :user_type";

        // Verifica se uma nova senha foi fornecida
        if (!empty($this->password_input)) { // Usa password_input aqui
            $query .= ", password_hash = :password_hash";
            // Gera o hash da nova senha
            $this->password_hash = password_hash($this->password_input, PASSWORD_BCRYPT);
        }

        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitiza os dados
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));
        $this->id = htmlspecialchars(strip_tags($this->id)); // Sanitiza o ID também

        // Vincula os valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':user_type', $this->user_type);
        $stmt->bindParam(':id', $this->id);

        // Vincula a senha se ela foi fornecida
        if (!empty($this->password_input)) {
            $stmt->bindParam(':password_hash', $this->password_hash);
        }

        // Executa a query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // NOVO: Método para deletar um usuário
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // Sanitiza o ID
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Vincula o ID
        $stmt->bindParam(1, $this->id);

        // Executa a query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>