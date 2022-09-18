<?php
require __DIR__ . './vendor/autoload.php';

use Src\Game;
use Src\Computer;
use Src\Calculation;
use Src\Helper;

ini_set('memory_limit', -1);
date_default_timezone_set('America/Sao_Paulo');

//Limpar terminal
function clearTerminal()
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        popen('cls', 'w');
    } else {
        popen('clear', 'w');
    }
};

//log Inicio
function logInicio()
{
    echo "\nInício --- " . date("H:i:s") . "\n";
};

//log Inicio
function logFim()
{
    echo "\n\nFim --- " . date("H:i:s") . "\n";
};

//Função para perguntar
function inputResp()
{
    $handle = fopen("php://stdin", "r");
    do {
        $line = fgets($handle);
    } while ($line == '');
    fclose($handle);
    return $line;
};

//remover quebra de linha
function removerQuebraLinha($str)
{
    $str = str_replace("\n", "", $str);
    $str = str_replace("\r", "", $str);
    $str = preg_replace('/\s/', ' ', $str);
    return $str;
};

# --------------------------------------------------
echo Helper::title('GERADOR DE JOGO PARA LOTOFACIL');
echo "\n Escolha uma opção:";
echo "\n 1 - Verificar se um número já foi jogado.";
echo "\n 2 - Jogar em modo teste.";
echo "\n 3 - Jogar.";
echo "\n 4 - Gerar txt com a representação visual dos resultados.";
echo "\n 5 - Atualizar Dataset.";
echo "\n 6 - Jogar em modo teste múltiplas vzs.";
echo "\n 0 - Sair.";
echo "\n\nOpçao: ";
$input = inputResp();
$title = 'GERADOR DE JOGO PARA LOTOFACIL';

switch (trim($input)) {

    case 0:
        # Cancelar
        clearTerminal();
        echo Helper::title($title);
        echo "\nCancelado!\n";
        break;
    case 1:
        clearTerminal();
        echo Helper::title($title);
        echo "\n\nDigite o número do jogo separado por traço ex: 1-2-10...: ";
        $resp = explode('-', removerQuebraLinha(inputResp()));
        $calcule = new Calculation();
        echo ($calcule->unprecedented($this->cal->getDataSetString(), $resp) == 0 ? 'Este jogo nunca saiu!' : 'este jogo já saiu!');
        break;
    case 2:
        clearTerminal();
        echo Helper::title($title);
        $game = new Game();
        $game->setModeTest();
        $game->play();
        break;
    case 3:
        clearTerminal();
        echo Helper::title($title);
        $game = new Game();
        $game->play();
        break;
    case 4:
        clearTerminal();
        echo Helper::title($title);
        $game = new Calculation();
        $game->withThe25();
        break;
    case 5:
        clearTerminal();
        echo Helper::title($title);
        $game = new Calculation();
        $game->updateDataset();
        break;
    case 6:
        clearTerminal();
        echo Helper::title($title);
        $game = new Game();
        $game->setModeTest();
        $game->generateGameMulti();
        break;

    default:
        echo "\nEssa opção não exite, tente novamente";
        break;
}

exit;
