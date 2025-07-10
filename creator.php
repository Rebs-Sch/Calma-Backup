<?php
ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);

class Creator {
    private $con;
    private $servidor ;
    private $banco;
    private $usuario;
    private $senha;
    private $tabelas;

    function __construct() {
        $this->criaDiretorios();
        $this->conectar();
        $this->buscaTabelas();
        $this->ClassesModel();
        $this->ClasseConexao();
        $this->ClassesControl();
        $this->ClassesView();
        $this->compactar();
        header("location:index.php?msg=2");
    }
    
    function criaDiretorios() {
        $dirs = [
            "sistema",
            "sistema/model",
            "sistema/control",
            "sistema/view",
            "sistema/view/styles",
            "sistema/dao"
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    header("Location:index.php?msg=0");
                }
            }
        }
    }

    function conectar() {
        $this->servidor=$_POST["servidor"];
        $this->banco=$_POST["banco"];
        $this->usuario=$_POST["usuario"];
        $this->senha=$_POST["senha"];
        
        try{
            $this->con = new PDO(
                "mysql:host=" . $this->servidor . ";dbname=" . $this->banco,
                $this->usuario,
                $this->senha
            );
        } catch (Exception $e) {
            header("Location:index.php?msg=1");
        }
    }

    //busca coisas
    function buscaTabelas(){
        $sql = "SHOW TABLES";
        $query = $this->con->query($sql);
        $this->tabelas = $query->fetchAll(PDO::FETCH_ASSOC);
    }

    function buscaAtributos($nomeTabela){
        $sql="show columns from ".$nomeTabela;
        $atributos = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        return $atributos;
    }
    
    public static function buscaBanco() {
        header('Content-Type: application/json'); // Retorna JSON
        
        $servidor = $_POST['servidor'] ?? '';
        $usuario = $_POST['usuario'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if (empty($servidor)) {
            echo json_encode(["erro" => "Servidor não informado."]);
            exit;
        }

        try {
            $pdo = new PDO(
                "mysql:host=$servidor",
                $usuario,
                $senha,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stm = $pdo->query("SHOW DATABASES");
            $bancos = $stm->fetchAll(PDO::FETCH_COLUMN);
            $ignorar = ['information_schema', 'mysql', 'performance_schema', 'sys']; 
            $bancosFiltrados = array_diff($bancos, $ignorar); 

            echo json_encode(["bancos" => array_values($bancosFiltrados)]);
        } catch (PDOException $e) {
            echo json_encode(["erro" => "Erro ao conectar: " . $e->getMessage()]);
        }
        exit; 
    }

    //Cria as classes
    function ClassesModel() {
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $atributos=$this->buscaAtributos($nomeTabela);
            $nomeAtributos="";
            $geters_seters="";
            foreach ($atributos as $atributo) {
                $atributo=$atributo->Field;
                $nomeAtributos.="\tprivate \${$atributo};\n";
                $metodo=ucfirst($atributo);
                $geters_seters.="\tfunction get".$metodo."(){\n";
                $geters_seters.="\t\treturn \$this->{$atributo};\n\t}\n";
                $geters_seters.="\tfunction set".$metodo."(\${$atributo}){\n";
                $geters_seters.="\t\t\$this->{$atributo}=\${$atributo};\n\t}\n";
            }
            $nomeTabela=ucfirst($nomeTabela);
            $conteudo = <<<EOT
<?php
class {$nomeTabela} {
{$nomeAtributos}
{$geters_seters}
}
?>
EOT;
            file_put_contents("sistema/model/{$nomeTabela}.php", $conteudo);

        }
    }
    function ClasseConexao(){
        $conteudo = <<<EOT
<?php
class Conexao {
    private \$server;
    private \$banco;
    private \$usuario;
    private \$senha;
    function __construct() {
        \$this->server = '[Informe aqui o servidor]';
        \$this->banco = '[Informe aqui o seu Banco de dados]';
        \$this->usuario = '[Informe aqui o usuário do banco de dados]';
        \$this->senha = '[Informe aqui a senha do banco de dados]';
    }
    function conectar() {
        try {
            \$conn = new PDO(
                "mysql:host=" . \$this->server . ";dbname=" . \$this->banco,\$this->usuario,
                \$this->senha
            );
            return \$conn;
        } catch (Exception \$e) {
            echo "Erro ao conectar com o Banco de dados: " . \$e->getMessage();
        }
    }
}
?>
EOT;
        file_put_contents("sistema/model/conexao.php", $conteudo);
    }

    function ClassesControl(){
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array)$tabela)[0];
            $nomeClasse=ucfirst($nomeTabela);
            $conteudo = <<<EOT
<?php
require_once("../model/{$nomeClasse}.php");
require_once("../dao/{$nomeClasse}Dao.php");
class {$nomeClasse}Control {
    private \${$nomeTabela};
    private \$acao;
    private \$dao;
    public function __construct(){
       \$this->{$nomeTabela}=new {$nomeClasse}();
      \$this->dao=new {$nomeClasse}Dao();
      \$this->acao=\$_GET["a"];
      \$this->verificaAcao(); 
    }
    function verificaAcao(){}
    function inserir(){}
    function excluir(){}
    function alterar(){}
    function buscarId({$nomeClasse} \${$nomeTabela}){}
    function buscaTodos(){}

}
new {$nomeClasse}Control();
?>
EOT;
            file_put_contents("sistema/control/{$nomeTabela}Control.php", $conteudo);
        }

    }

    function ClassesView() {
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $atributos = $this->buscaAtributos($nomeTabela);

            $inputs = '';
            
            foreach ($atributos as $atributo) {
                if($atributo->key == 'PRI'){
                    continue;
                }elseif (is_int($atributo)){
                    $inputs .= "<div class='formgroup'>\n<label>{$atributo}:</label>\n<input type='number' id='{$atributo}' name='{$atributo}' required>\n</div>\n";
                }else{
                    $inputs .= "<div class='formgroup'>\n<label>{$atributo}:</label>\n<input type='text' id='{$atributo}' name='{$atributo}' required>\n</div>\n";
                }
            }

            $conteudo = <<<EOT
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$nomeTabela}</title>
    <link rel="stylesheet" href="styles/{$nomeTabela}Style.css">
</head>
<body>
    <h1>Cadastro de {$nomeTabela}</h1>

    <form action="">
        {$inputs}
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
EOT;

        file_put_contents("sistema/view/{$nomeTabela}.php", $conteudo);

        $conteudoCSS = <<<EOT
*{
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}

body{
    background-color: #0F0F0F;
    color: white;

    height: 100svh;

    display: flex;
    align-items: center;
    justify-content: center;

    font-family: "open sans", sans-serif;
}

h1{
    color: #B7FE66;
}

form{
    background-color: #232323;

    padding: 20px;
    border-radius: 10px;
}

.formgroup{
 padding: 5px 0;
}

label{
    display: block;

    color: #B7FE66;

    margin-top: 10px;
}

input{
    background-color: #333333;
    color: #ffffff;

    width: 100%;
    padding: 5px;

    border-radius: 5px;
    border: 2px, solid, #B7FE66;
}

button{
    background-color: #B7FE66;
    color: #FFFFFF;
    border: none;
    border-radius: 3px;

    padding: 5px;
    margin-top: 5px;
}
EOT;
        file_put_contents("sistema/view/styles/{$nomeTabela}Style.css", $conteudoCSS);

        }
    }
    
    function compactar(){
        $folderToZip = "sistema";
        $outputZip = "sistema.zip";
        $zip = new ZipArchive();
        
        if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE){
            return false;
        }
        $folderPath = realpath($folderToZip);
        
        if(!is_dir($folderPath)){
            return false;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $name => $file) {
            if(!$file->isDir()){
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        return $zip->close();
    }
}

if (isset($_POST['acao']) && $_POST['acao'] === 'buscar_bancos') {
    Creator::buscaBanco();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['acao'])) {
    new Creator();
}
