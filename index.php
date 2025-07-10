<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Formulário de Conexão</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="container">

<form action="creator.php" method="POST">
    <?php
ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);

        include 'mensagens.php';
        if (isset($_GET['msg'])){
            $msg = $_GET['msg'];
            $classe = $msg == 2?'mensagem' : 'mensagem-erro';
            echo "<div class='".$classe."'>" . ($mensagens[$msg] ?? "Erro desconhecido") . "</div>";
        }
    ?>
    
    <h1>Criador MVC 2000</h1>

    <label for="usuario">Usuário:</label>
    <input type="text" id="usuario" name="usuario" placeholder="Informe o usuário" required>

    <label for="senha">Senha:</label>
    <input type="password" id="senha" name="senha" placeholder="Insira a senha" required>
    
    <label for="servidor">Servidor:</label>
    <input type="text" id="servidor" name="servidor" placeholder="Informe o servidor" required>
    
    <div>
        <label for="banco">Banco de Dados:</label>
        <select name="banco" id="banco">
            <option value="" selected disabled hidden>Carregando...</option>
        </select>
    </div>

    <button type="submit">Enviar</button>
</form>
</div>

<script>
document.getElementById("servidor").addEventListener("blur", function() {
    const servidor = this.value;
    const usuario = document.getElementById("usuario").value;
    const senha = document.getElementById("senha").value;

    if (!servidor) return;

    const selectBanco = document.getElementById("banco");
    selectBanco.innerHTML = '<option value="" disabled selected>Carregando bancos...</option>';

    fetch('creator.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `servidor=${servidor}&usuario=${usuario}&senha=${senha}&acao=buscar_bancos`
    })
    .then(response => response.json())
    .then(data => {
        selectBanco.innerHTML = '<option value="" disabled selected>Selecione um banco...</option>';

        if (data.erro) {
            selectBanco.innerHTML = '<option value="" disabled selected>Erro ao carregar bancos</option>';
            return;
        }

        data.bancos.forEach(banco => {
            const option = document.createElement("option");
            option.value = banco;
            option.textContent = banco;
            selectBanco.appendChild(option);
        });
    })
    .catch(erro => {
        selectBanco.innerHTML = '<option value="" disabled selected>Falha na conexão</option>';
    });
});
</script>

</body>
</html>
