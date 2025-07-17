<?php
// public/index.php

session_start(); // Inicia a sessão PHP

// Verifica se o usuário JÁ ESTÁ logado
if (isset($_SESSION['user_id'])) {
    // Se estiver logado, redireciona para o dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Se NÃO estiver logado, redireciona para a página de login
    header('Location: login.php');
    exit;
}
?>