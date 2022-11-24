<?php

namespace Src;

use Src\Game;
use Src\Helper;
use Src\Computer;
use Src\Calculation;

class Controller
{
    private $title = 'GERADOR DE JOGO PARA LOTOFACIL';
    private $subtitle = ' - TESTE';

    /**
     * Init
     */
    public function __construct()
    {
        ini_set('memory_limit', -1);
        date_default_timezone_set('America/Sao_Paulo');
    }

    /**
     * Rodar programar
     *
     * @return void
     */
    public function run()
    {
        Helper::clearTerminal();
        echo Helper::title($this->title);

        echo "\n Escolha uma opção:"
            . "\n 1 - Verificar se um número já foi jogado."
            . "\n 2 - Jogar."
            . "\n 22- Jogar em modo teste."
            . "\n 3 - Modo ML."
            . "\n 33- Modo de teste ML."
            . "\n 4 - Atualizar Dataset."
            . "\n 5 - Jogar em modo teste múltiplas vzs."
            . "\n 6 - Gerar jogo com método 3x3"
            . "\n 66- Gerar jogo com método 3x3 em modo teste"
            . "\n 7- Outros."
            . "\n 0 - Sair."
            . "\n\nOpçao: ";

        $this->options(HElper::inputResp());
    }

    /**
     * Opções de comandos
     *
     * @param string $input 
     * @return void
     */
    public function options($input)
    {
        Helper::clearTerminal();
        $option = 'option' . $input;

        if (method_exists($this, $option)) {
            $this->$option();
        } else {
            echo "\nEssa opção $input não exite, tente novamente\n";
        }
    }

    /**
     * Cancelar
     *
     * @return void
     */
    public function option0()
    {
        echo Helper::title($this->title);
        echo "\nCancelado!\n";
    }

    /**
     * Verificar se um número já foi jogado
     *
     * @return void
     */
    public function option1()
    {
        echo Helper::title($this->title);
        echo "\n\nDigite o número do jogo separado por traço ex: 1-2-10...: ";
        $resp = explode('-', Helper::inputResp());
        $cal = new Calculation();
        $check = $cal->unprecedented($cal->getDataSetString(), $resp);
        echo "\n";
        if ($check == 0) {
            echo 'Este jogo nunca saiu!';
        } elseif ($check == -1) {
            echo 'Este jogo é inválido!';
        } else {
            echo 'Este jogo já saiu!';
        }
        echo "\n";
    }

    /**
     * Jogar
     *
     * @return void
     */
    public function option2()
    {
        echo Helper::title($this->title);
        $game = new Game();
        $game->play();
    }

    /**
     * Jogar em modo teste.
     *
     * @return void
     */
    public function option22()
    {
        echo Helper::title($this->title . $this->subtitle);
        $game = new Game();
        $game->setModeTest();
        $game->play();
    }

    /**
     * UModo ML.
     *
     * @return void
     */
    public function option3()
    {
        echo Helper::title($this->title);
        Computer::run();
    }

    /**
     * Modo de teste ML
     *
     * @return void
     */
    public function option33()
    {
        echo Helper::title($this->title . $this->subtitle);
        Computer::run(true);
    }

    /**
     * Atualizar Dataset.
     *
     * @return void
     */
    public function option4()
    {
        echo Helper::title($this->title);
        $game = new Calculation();
        $game->updateDataset();
    }

    /**
     * Jogar em modo teste múltiplas vzs.
     *
     * @return void
     */
    public function option5()
    {
        echo Helper::title($this->title . $this->subtitle);
        $game = new Game();
        $game->setModeTest();
        $game->generateGameMulti();
    }

    /**
     * Gerar jogo com método 3x3
     *
     * @return void
     */
    public function option6()
    {
        echo Helper::title($this->title);
        $game = new Calculation();
        $game->game3x3();
    }

    /**
     * Gerar jogo com método 3x3 em modo teste
     *
     * @return void
     */
    public function option66()
    {
        echo Helper::title($this->title . $this->subtitle);
        $game = new Calculation();
        $game->game3x3(true);
    }

    /**
     * Jogar em modo teste.
     *
     * @return void
     */
    public function option7()
    {
        echo Helper::title($this->title . $this->subtitle);
        $game = new Game();
        $game->other();
    }
}
