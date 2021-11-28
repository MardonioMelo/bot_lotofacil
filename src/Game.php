<?php

namespace Src;

use Src\Calculation;

class Game
{

    private $cal;
    private $test;

    public function __construct()
    {
        $this->cal = new Calculation();
        // $this->cal->withThe25(); //criar aquivo de texto com representação das dezenas
    }

    /**
     * Play para gerar jogo
     *
     * @param boolean $test Informe true para prever o ultimo jogo que saiu ou false para prever o proximo jogo que não saiu
     * @return void
     */
    public function play($test = false): void
    {
        $this->test = $test;
        $jogo = $this->generateGame();
        sort($jogo);

        //Teste
        if ($test) {
            $last_games = $this->cal->getLastGames(1);
            $inedito = $this->cal->unprecedented(implode("-", $jogo));
            print_r("\n\n------------------------------------> \n");
            print_r(" Análise para Teste\n");
            print_r("------------------------------------> \n");
            print_r('Inedito : ' . ($inedito == 0 ? 'Sim' : "Não ($inedito)") . "\n");
            print_r('Impar   : ' . $this->cal->qtdImparPar($jogo)['impar'] . " (8)\n");
            print_r('Par     : ' .  $this->cal->qtdImparPar($jogo)['par'] . " (7)\n");
            print_r('Soma    : ' . $this->cal->sumDezene($jogo) . " (166 - 220)\n");
            print_r('Total   : ' . count($jogo) . " (15)\n");
            print_r('Previsto: ' . implode("-", $jogo) . "\n");       
            print_r('Correto : ' . implode("-", $last_games[array_key_last($last_games)]) . ' (' . (array_key_last($last_games) + 1) . ")\n");
            print_r('Acertos : ' . implode("-", $this->checkHits($jogo)) . ' (' . count($this->checkHits($jogo)) . ")\n");
        } else {
            print_r("\n\n------------------\n Análise para Jogar \n------------------ \n");
            print_r('Impar   : ' . $this->cal->qtdImparPar($jogo)['impar'] . " (8)\n");
            print_r('Par     : ' .  $this->cal->qtdImparPar($jogo)['par'] . " (7)\n");
            print_r('Soma    : ' . $this->cal->sumDezene($jogo) . " (166 - 220)\n");
            print_r('Total   : ' . count($jogo) . " (15)\n");
            print_r('Previsto: ' . implode("-", $jogo) . "\n");
        }
    }

    /**
     * Verificar intervalos
     *
     * @return void
     */
    public function checkInterval()
    {
        $this->test = false;
        $qtd_analysis = 500;
        $last_games = $this->cal->getLastGames($qtd_analysis + ($this->test ? 1 : 0));

        $i = 1;
        $arr = [];
        foreach ($last_games as $key => $item) {
            if ((int)$item[0] == 1) {               
                $i++;               
            } else {               
                $arr[$i] = empty($arr[$i])? 1: $arr[$i] + 1;                
            }
        }

        foreach ($arr as $item) {
          //  echo str_repeat("-", $item) . $item;
          echo "-" . $item;
        }
         print_r(" | ".count($arr)/3);
    }

    /**
     * Gerar Jogo
     *
     * @param integer $qtd_analysis Qtd a ser analisado
     * @param integer $qtd_dez_last Qtd das atrasadas
     * @param integer $qtd_num_plus Qtd dos mais sorteados
     * @param integer $qtd_num_pri_s Qtd de numeros primos que saiu no ultimo sorteio
     * @param integer $qtd_num_pri_ns Qtd de numeros primos que não saiu no ultimo sorteio
     * @return array
     */
    private function generateGame(
        $qtd_analysis = 20,
        $qtd_dez_last = 5,
        $qtd_num_plus = 6,
        $qtd_num_pri_s = 3,
        $qtd_num_pri_ns = 3
    ): array {

        $jogo = []; //jogo vazio
        $numPri = [2, 3, 5, 7, 11, 13, 17, 19, 23];
        $numPar = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24];
        $numImp = [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25];

        # Selecionar os ultimos concursos (analisar dezenas que saiu e dezenas mais atrasadas)
        $last_games = $this->cal->getLastGames($qtd_analysis + ($this->test ? 1 : 0));
        if ($this->test) unset($last_games[array_key_last($last_games)]);

        # Escolher as dezenas atrasadas de preferencia
        $laterNumbers = $this->cal->laterNumbers($last_games); //números mais atrasados     

        # Adicionar n números mais atrasadas
        foreach ($laterNumbers as $key => $item) {

            if ($item >= 2 && $qtd_dez_last > 0) {
                $jogo[] = $key;
                $qtd_dez_last--;
            }
        }

        # Os numeros mais sorteados nos ultimos concursos   
        $countFrequency = $this->cal->countFrequency($last_games); //frequencia das dezenas   

        # Adicionar n números mais sorteados
        foreach ($countFrequency as $key => $item) {

            if ($qtd_num_plus > 0) {
                $jogo[] = $key;
                $qtd_num_plus--;
            }
        }

        # Selecione o ultimo concurso
        $endGame = end($last_games);

        # Adicionar n números primos saido no ultimo concurso
        foreach ($numPri as $key => $item) {

            if (in_array($item, $endGame) && $qtd_num_pri_s > 0) {
                $jogo[] = $item;
                $qtd_num_pri_s--;
            }
        }     

        # Adicionar n números primos que não saiu no ultimo concurso
        foreach ($numPri as $key => $item) {       

            if (!in_array($item, $endGame) && $qtd_num_pri_ns > 0) {
                $jogo[] = $item;
                $qtd_num_pri_ns--;
            }
        }

        # Os números 2,3,4 acompanham o número 1
        if ((in_array(2, $jogo) || in_array(3, $jogo) || in_array(4, $jogo)) && !in_array(1, $jogo)) {
            $jogo[] = 1;
        }

        # Os números 22,23,24,25 acompanham o número 15
        if ((in_array(22, $jogo) || in_array(23, $jogo) || in_array(24, $jogo)) || in_array(25, $jogo) && !in_array(15, $jogo)) {
            $jogo[] = 15;
        }

        # Remover repetidas adicionadas
        $jogo = array_unique($jogo);

        // $checkMaxMin = $this->cal->chackMaxMin($last_games); //max e min de cada bola
        // print_r($checkMaxMin);

        # ----------------------------------Regras ----------------------------------------->   

        # escolher de 7 a 10 dezenas do concurso anterior, preferencia 9     
        # não escolher mais que 8 dezenas seguidas 
        # escolher de 2 a 5 dezenas entre 20 e 25 ou escolher de 4 a 8 dezenas seguidas entre 1 a 10
        # não jogar jogo repetido  
        # entre os números primos, 3 devem ter saido no ultimo concurso e 2 não     
        return $jogo;
    }

    /**
     * Verificar acertos referente ao proximo jogo de teste
     *
     * @param array $jogo
     * @return array
     */
    private function checkHits(array $jogo): array
    {
        $last_games = $this->cal->getLastGames(1);
        $hits = [];

        foreach ($last_games[array_key_last($last_games)] as $item) {
            if (in_array($item, $jogo)) {
                $hits[] = $item;
            }
        }

        return $hits;
    }
}
