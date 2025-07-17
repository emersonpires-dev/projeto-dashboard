<?php
// src/Models/Expense.php

require_once __DIR__ . '/../Core/Database.php';

class Expense {
    private $conn;
    private $table_name = "expenses"; // Tabela principal para gastos
    private $categories_table = "categories"; // Tabela de categorias

    public $id;
    public $user_id;
    public $description;
    public $value;
    public $category_id;
    public $expense_date;
    public $created_at;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    // Método para adicionar um novo gasto
    public function add() {
        // Valida se a categoria existe
        if (!$this->categoryExists($this->category_id)) {
            return false; // Categoria inválida
        }

        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    description = :description,
                    value = :value,
                    category_id = :category_id,
                    expense_date = :expense_date";

        $stmt = $this->conn->prepare($query);

        // Sanitiza os dados
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->value = htmlspecialchars(strip_tags($this->value)); // O valor é numérico, mas strip_tags é bom.
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->expense_date = htmlspecialchars(strip_tags($this->expense_date));

        // Vincula os valores
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':value', $this->value);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':expense_date', $this->expense_date);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Método auxiliar para verificar se uma categoria existe
    public function categoryExists($category_id) {
        $query = "SELECT id FROM " . $this->categories_table . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $category_id = htmlspecialchars(strip_tags($category_id));
        $stmt->bindParam(1, $category_id);
        $stmt->execute();

        return ($stmt->rowCount() > 0);
    }

    // Método para buscar todas as categorias (útil para popular o select no formulário)
    public function getAllCategories() {
        $query = "SELECT id, name FROM " . $this->categories_table . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para buscar todos os gastos de um usuário específico, COM PAGINAÇÃO
    public function getAllExpensesByUserId($limit = null, $offset = null) {
        $query = "SELECT
                    e.id, e.description, e.value, e.expense_date, e.created_at,
                    c.name as category_name,
                    e.category_id  /* <<--- GARANTIDO: e.category_id está sendo selecionado */
                  FROM
                    " . $this->table_name . " e
                  LEFT JOIN
                    " . $this->categories_table . " c ON e.category_id = c.id
                  WHERE
                    e.user_id = :user_id";

        // Adicionar limite e offset para paginação, se fornecidos
        if ($limit !== null && $offset !== null) {
            $query .= " ORDER BY e.expense_date DESC, e.created_at DESC LIMIT :limit OFFSET :offset";
        } else {
            $query .= " ORDER BY e.expense_date DESC, e.created_at DESC";
        }


        $stmt = $this->conn->prepare($query);

        // Sanitiza o user_id
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);

        // Vincula os parâmetros de paginação, se fornecidos
        if ($limit !== null && $offset !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt; // Retorna o objeto PDOStatement
    }

    // Método para contar o total de gastos de um usuário (para calcular o número de páginas)
    public function countAllExpensesByUserId() {
        $query = "SELECT COUNT(*) as total_count
                  FROM " . $this->table_name . "
                  WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_count'];
    }

    // Método para obter o resumo de gastos por categoria para um usuário específico (para gráficos)
    public function getExpensesSummaryByCategoryByUserId() {
        $query = "SELECT
                    c.name as category_name,
                    SUM(e.value) as total_spent
                  FROM
                    " . $this->table_name . " e
                  LEFT JOIN
                    " . $this->categories_table . " c ON e.category_id = c.id
                  WHERE
                    e.user_id = :user_id
                  GROUP BY
                    c.name
                  ORDER BY
                    total_spent DESC";

        $stmt = $this->conn->prepare($query);

        // Sanitiza o user_id
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Vincula o user_id
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna os resultados como array associativo
    }

    // Método para buscar um único gasto pelo ID
    public function getOneExpense() {
        $query = "SELECT
                    e.id, e.user_id, e.description, e.value, e.category_id, e.expense_date,
                    c.name as category_name
                  FROM
                    " . $this->table_name . " e
                  LEFT JOIN
                    " . $this->categories_table . " c ON e.category_id = c.id
                  WHERE
                    e.id = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->user_id = $row['user_id'];
            $this->description = $row['description'];
            $this->value = $row['value'];
            $this->category_id = $row['category_id'];
            $this->expense_date = $row['expense_date'];
            $this->category_name = $row['category_name'];
            return true;
        }
        return false;
    }

    // Método para atualizar um gasto existente
    public function updateExpense() {
        // Valida se a categoria existe
        if (!$this->categoryExists($this->category_id)) {
            return false; // Categoria inválida
        }

        $query = "UPDATE " . $this->table_name . "
                SET
                    description = :description,
                    value = :value,
                    category_id = :category_id,
                    expense_date = :expense_date
                WHERE
                    id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitiza os dados
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->value = htmlspecialchars(strip_tags($this->value));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->expense_date = htmlspecialchars(strip_tags($this->expense_date));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Vincula os valores
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':value', $this->value);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':expense_date', $this->expense_date);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Método para deletar um gasto
    public function deleteExpense() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitiza os IDs
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Vincula os IDs
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Método para obter o total de gastos do mês atual para um usuário
    public function getTotalSpentThisMonthByUserId() {
        $query = "SELECT SUM(value) as total_spent
                  FROM " . $this->table_name . "
                  WHERE user_id = :user_id
                  AND expense_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
                  AND expense_date <= LAST_DAY(NOW())";

        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_spent'] ?? 0.00;
    }

    // Método para obter a categoria com o maior gasto do mês atual para um usuário
    public function getTopCategoryThisMonthByUserId() {
        $query = "SELECT
                    c.name as category_name,
                    SUM(e.value) as total_spent
                  FROM
                    " . $this->table_name . " e
                  LEFT JOIN
                    " . $this->categories_table . " c ON e.category_id = c.id
                  WHERE
                    e.user_id = :user_id
                    AND e.expense_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
                    AND e.expense_date <= LAST_DAY(NOW())
                  GROUP BY
                    c.name
                  ORDER BY
                    total_spent DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['category_name'] ?? 'N/A';
    }

    // Método para obter o próximo gasto futuro para um usuário
    public function getNextUpcomingExpenseByUserId() {
        $query = "SELECT
                    e.description, e.value, e.expense_date
                  FROM
                    " . $this->table_name . " e
                  WHERE
                    e.user_id = :user_id
                    AND e.expense_date >= CURDATE()
                  ORDER BY
                    e.expense_date ASC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?? null;
    }

    // Método para buscar gastos com filtros para um usuário específico (para relatórios)
    public function getFilteredExpensesByUserId($filters = []) {
        $query = "SELECT
                    e.id, e.description, e.value, e.expense_date, e.created_at,
                    c.name as category_name,
                    e.category_id
                  FROM
                    " . $this->table_name . " e
                  LEFT JOIN
                    " . $this->categories_table . " c ON e.category_id = c.id
                  WHERE
                    e.user_id = :user_id";

        $bindParams = [];
        $bindParams[':user_id'] = htmlspecialchars(strip_tags($this->user_id));

        // Adicionar filtros de data
        if (!empty($filters['start_date'])) {
            $query .= " AND e.expense_date >= :start_date";
            $bindParams[':start_date'] = htmlspecialchars(strip_tags($filters['start_date']));
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND e.expense_date <= :end_date";
            $bindParams[':end_date'] = htmlspecialchars(strip_tags($filters['end_date']));
        }

        // Adicionar filtro de categoria
        if (!empty($filters['category_id'])) {
            $query .= " AND e.category_id = :category_id_filter"; // Use um nome diferente para o parâmetro
            $bindParams[':category_id_filter'] = htmlspecialchars(strip_tags($filters['category_id']));
        }
        
        // Adicionar filtro por descrição/busca (exemplo simples com LIKE)
        if (!empty($filters['search_term'])) {
            $query .= " AND e.description LIKE :search_term";
            $bindParams[':search_term'] = '%' . htmlspecialchars(strip_tags($filters['search_term'])) . '%';
        }


        $query .= " ORDER BY e.expense_date DESC, e.created_at DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($bindParams as $param => $value) {
            $stmt->bindValue($param, $value); // bindValue é mais seguro para loops com strings
        }
        
        $stmt->execute();
        return $stmt; // Retorna o PDOStatement para iteração ou fetchAll
    }

    // Método para obter o resumo de gastos mensais para um usuário (para gráfico de linha)
    public function getMonthlyExpensesSummaryByUserId($filters = []) {
        $query = "SELECT
                    DATE_FORMAT(e.expense_date, '%Y-%m') as expense_month,
                    SUM(e.value) as total_spent_month
                  FROM
                    " . $this->table_name . " e
                  WHERE
                    e.user_id = :user_id";

        $bindParams = [':user_id' => htmlspecialchars(strip_tags($this->user_id))];

        // Adicionar filtros de data
        if (!empty($filters['start_date'])) {
            $query .= " AND e.expense_date >= :start_date";
            $bindParams[':start_date'] = htmlspecialchars(strip_tags($filters['start_date']));
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND e.expense_date <= :end_date";
            $bindParams[':end_date'] = htmlspecialchars(strip_tags($filters['end_date']));
        }

        $query .= " GROUP BY expense_month ORDER BY expense_month ASC";

        $stmt = $this->conn->prepare($query);

        foreach ($bindParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna os resultados como array associativo
    }

    // Método para obter a distribuição percentual de gastos por categoria para um usuário (para gráfico de pizza/donut)
    public function getCategoryDistributionByUserId($filters = []) {
        $query = "SELECT
                    c.name as category_name,
                    SUM(e.value) as total_spent,
                    (SUM(e.value) / (SELECT SUM(value) FROM " . $this->table_name . " WHERE user_id = :user_id_total_sum AND expense_date >= :start_date_total AND expense_date <= :end_date_total )) * 100 as percentage
                  FROM
                    " . $this->table_name . " e
                  LEFT JOIN
                    " . $this->categories_table . " c ON e.category_id = c.id
                  WHERE
                    e.user_id = :user_id";

        $bindParams = [':user_id' => htmlspecialchars(strip_tags($this->user_id))];
        $bindParams[':user_id_total_sum'] = htmlspecialchars(strip_tags($this->user_id)); // Duplica para subquery

        // Adicionar filtros de data
        if (!empty($filters['start_date'])) {
            $query .= " AND e.expense_date >= :start_date";
            $bindParams[':start_date'] = htmlspecialchars(strip_tags($filters['start_date']));
            $bindParams[':start_date_total'] = htmlspecialchars(strip_tags($filters['start_date'])); // Duplica para subquery
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND e.expense_date <= :end_date";
            $bindParams[':end_date'] = htmlspecialchars(strip_tags($filters['end_date']));
            $bindParams[':end_date_total'] = htmlspecialchars(strip_tags($filters['end_date'])); // Duplica para subquery
        }
        // Nota: Para filtros de categoria e search_term, seria necessário replicá-los na subquery também,
        // mas para manter a complexidade gerenciável, os calculamos apenas para a query principal por enquanto.

        $query .= " GROUP BY c.name ORDER BY total_spent DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($bindParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna os resultados como array associativo
    }
}
?>