<?php
// public/export_expenses.php
// Script para exportar gastos para CSV

session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/Models/Expense.php'; // Inclui a classe Expense

$expense = new Expense();
$expense->user_id = $_SESSION['user_id'];

// Obter os filtros da URL (os mesmos usados em reports.php)
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'search_term' => $_GET['search_term'] ?? ''
];

// Lógica para definir datas padrão se não forem fornecidas (mesma de reports.php)
if (empty($filters['start_date'])) {
    $filters['start_date'] = date('Y-m-01'); // Início do mês atual
}
if (empty($filters['end_date'])) {
    $filters['end_date'] = date('Y-m-t'); // Último dia do mês atual
}

// Buscar gastos com base nos filtros
$stmt = $expense->getFilteredExpensesByUserId($filters);
$filteredExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Geração do CSV ---

$filename = 'relatorio_gastos_' . date('Ymd_His') . '.csv';

// Cabeçalhos HTTP para download do CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="' . $filename . '"');

// Abre o output para escrita
$output = fopen('php://output', 'w');

// Adiciona o BOM (Byte Order Mark) para garantir que caracteres especiais (UTF-8) sejam exibidos corretamente no Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalhos do CSV
fputcsv($output, ['Data', 'Descricao', 'Categoria', 'Valor']);

// Dados
if (!empty($filteredExpenses)) {
    foreach ($filteredExpenses as $row) {
        fputcsv($output, [
            date('d/m/Y', strtotime($row['expense_date'])),
            $row['description'],
            $row['category_name'],
            number_format($row['value'], 2, ',', '.') // Formata o valor
        ]);
    }
}

fclose($output); // Fecha o output
exit; // Garante que nenhum outro conteúdo seja enviado
?>