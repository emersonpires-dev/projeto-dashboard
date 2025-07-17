<?php
// public/register.php

session_start(); // Inicia a sessão PHP

// Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Cadastro';
include '../src/Views/partials/header.php'; // Inclui o cabeçalho
?>

<main class="flex-1 overflow-y-auto p-6 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Crie sua Conta</h2>

        <?php
        require_once '../src/Models/User.php';

        $message = ''; // Variável para armazenar mensagens de sucesso/erro

        // Verifica se o formulário foi submetido (método POST)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $user = new User();

            $user->name = $_POST['name'] ?? '';
            $user->email = $_POST['email'] ?? '';
            $user->password_input = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $user->user_type = 'viewer';

            // Validação de campos obrigatórios e formato de e-mail
            if (empty($user->name) || empty($user->email) || empty($user->password_input) || empty($confirm_password)) {
                $message = 'Todos os campos são obrigatórios!';
            } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Formato de e-mail inválido.';
            } elseif ($user->password_input !== $confirm_password) {
                $message = 'As senhas não coincidem.';
            } else {
                // Tenta registrar o usuário
                $registerResult = $user->register(); // Agora pode retornar true ou uma string de erro

                if ($registerResult === true) {
                    $message = 'Usuário registrado com sucesso! Você pode <a href="login.php" class="font-bold underline">fazer login</a>.';
                    $_POST = array(); // Limpa os campos do formulário
                } else {
                    $message = $registerResult; // Pega a mensagem de erro retornada pelo método
                    // Se a mensagem for "O e-mail já está em uso.", etc.
                }
            }
        }
        // Exibe a mensagem de sucesso ou erro
        if (!empty($message)) {
            $isSuccess = (strpos($message, 'sucesso') !== false); // Heurística simples para verificar sucesso
            echo '<div class="bg-' . ($isSuccess ? 'green' : 'red') . '-100 border border-' . ($isSuccess ? 'green' : 'red') . '-400 text-' . ($isSuccess ? 'green' : 'red') . '-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($message) . '</div>';
        }
        ?>

        <form action="register.php" method="POST">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nome:</label>
                <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Seu Nome Completo" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="seuemail@exemplo.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Senha:</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" placeholder="********" required>
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirme a Senha:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" placeholder="********" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Cadastrar
                </button>
            </div>
            <p class="text-center text-gray-600 text-sm mt-4">
                Já tem uma conta? <a href="login.php" class="text-blue-600 hover:text-blue-800 font-bold">Acesse aqui</a>.
            </p>
        </form>
    </div>
</main>

<?php include '../src/Views/partials/footer.php'; ?>