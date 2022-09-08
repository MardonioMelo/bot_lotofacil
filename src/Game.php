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
        $this->test = false;
    }

    /**
     * Play para gerar jogo
     *  
     * @return void
     */
    public function play(): void
    {
        $jogo = $this->generateGame();
        $analysis = $this->analysis($jogo);
        echo $analysis['log'];
    }

    /**
     * Obter wordlist de todos os possíveis jogos
     *
     * @return array
     */
    public function playWordlist(): array
    {
        $file = 'wordlist_lotofacil.txt'; //3.268.760 combinações   
        $arr = [];

        if (file_exists($file)) {
            $arr = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            unset($arr[0]);
        }
        return $arr;
    }

    /**
     * Gerar Jogo
     *
     * @param integer $qtd_analysis Qtd a ser analisado
     * @param integer $qtd_dez_last Qtd das atrasadas
     * @param integer $qtd_num_plus Qtd dos mais sorteados
     * @param integer $qtd_num_pri_s Qtd de números primos que saiu no ultimo sorteio
     * @param integer $qtd_num_pri_ns Qtd de numeros primos que não saiu no ultimo sorteio
     * @return array
     */
    private function generateGame($qtd_analysis = 100, $qtd_dez_last = 5, $qtd_num_plus = 4, $qtd_num_pri_s = 3, $qtd_num_pri_ns = 2): array
    {
        $jogo = []; //jogo vazio
        $numPri = [2, 3, 5, 7, 11, 13, 17, 19, 23];
        $numPar = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24];
        $numImp = [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25];

        # Selecionar os últimos concursos (analisar dezenas que saiu e dezenas mais atrasadas)
        $last_games = $this->cal->getLastGames($qtd_analysis + ($this->test ? 1 : 0));
        if ($this->test) unset($last_games[array_key_last($last_games)]);

        # Escolher as dezenas atrasadas de preferencia
        $laterNumbers = $this->cal->laterNumbers($last_games); //números mais atrasados     

        # Adicionar n números mais atrasadas
        foreach ($laterNumbers as $key => $item) {

            if ($item >= 3 && $qtd_dez_last > 0) {
                $jogo[] = $key;
                $qtd_dez_last--;
            }
        }

        # Os números mais sorteados nos últimos concursos   
        $countFrequency = $this->cal->countFrequency($last_games); //frequencia das dezenas   

        # Adicionar n números mais sorteados
        $add_num_plus = $qtd_num_plus;
        foreach ($countFrequency as $key => $item) {

            if ($add_num_plus > 0) {
                $jogo[] = $key;
                $add_num_plus--;
            }
        }

        # Selecione o ultimo concurso
        $endGame = end($last_games);

        # Adicionar de preferencia 3 números primos que saiu no último concurso
        foreach ($numPri as $key => $item) {

            if (in_array($item, $endGame) && $qtd_num_pri_s > 0) {
                $jogo[] = $item;
                $qtd_num_pri_s--;
            }
        }

        # Adicionar de preferencia 2 números primos que não saiu no ultimo concurso
        foreach ($numPri as $key => $item) {

            if (!in_array($item, $endGame) && $qtd_num_pri_ns > 0) {
                $jogo[] = $item;
                $qtd_num_pri_ns--;
            }
        }

        # Remover repetidas adicionadas
        $jogo = array_unique($jogo);

        # Escolher de 7 a 10 dezenas do concurso anterior, preferencia 9 dezenas
        $yes = $not = [];
        foreach ($jogo as $key => $item) {
            if (in_array($item, $endGame)) {
                $yes[] = $item;
            } else {
                $not[] = $item;
            }
        }
        if (count($yes) < 7) {
            $n_diff = 7 - count($yes);
            for ($i = 0; $i < $n_diff; $i++) {
                $jogo[] = $not[rand(0, count($not) - 1)];
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
        sort($jogo);

        //Completar quantidade de dezenas que faltam para 15 com os números que mais saiu
        if (count($jogo) < 15) {
            $add_dez = (15 - count($jogo)) +  $qtd_num_plus;
            $jogo = $this->generateGame($qtd_analysis, $qtd_dez_last, $add_dez);
        }

        # Verificar se cada bola estar entre o máximo e minimo de sua posição
        $checkMaxMin = $this->cal->chackMaxMin($last_games); //max e min de cada bola
        $i = 0;
        foreach ($jogo as $bola) {
            $i++;
            if($bola < $checkMaxMin['Bola'.$i]['min'] && $bola > $checkMaxMin['Bola'.$i]['max']){
                echo "Fora do range Bola-$i: $bola";
            }
        }

        # não escolher mais que 8 dezenas seguidas 
        # não jogar jogo repetido 

        return $jogo;
    }

    /**
     * Realizar analise do jogo
     *
     * @param string Jogo gerado com o método $this->generateGame()
     * @return array
     */
    public function analysis($jogo): array
    {
        $result['log'] = '';
        if ($this->test) {
            $last_games = $this->cal->getLastGames(1);
            $inedito = $this->cal->unprecedented(implode("-", $jogo));
            $result['log'] .= "\n\n------------------------------------> \n";
            $result['log'] .= " Análise para Teste\n";
            $result['log'] .= "------------------------------------> \n";
            $result['log'] .= 'Inedito : ' . ($inedito == 0 ? 'Sim' : "Não ($inedito)") . "\n";
            $result['log'] .= 'Impar   : ' . $this->cal->qtdImparPar($jogo)['impar'] . " (8)\n";
            $result['log'] .= 'Par     : ' . $this->cal->qtdImparPar($jogo)['par'] . " (7)\n";
            $result['log'] .= 'Soma    : ' . $this->cal->sumDezene($jogo) . " (166 - 220)\n";
            $result['log'] .= 'Total   : ' . count($jogo) . " (15)\n";
            $result['log'] .= 'Previsto: ' . implode("-", $jogo) . "\n";
            $result['log'] .= 'Correto : ' . implode("-", $last_games[array_key_last($last_games)]) . ' (' . (array_key_last($last_games) + 1) . ")\n";
            $result['log'] .= 'Acertos : ' . implode("-", $this->cal->checkHits($jogo)) . ' (' . count($this->cal->checkHits($jogo)) . ")\n";
        } else {
            $result['log'] .= "\n\n------------------\n Análise para Jogar \n------------------ \n";
            $result['log'] .= 'Impar   : ' . $this->cal->qtdImparPar($jogo)['impar'] . " (8)\n";
            $result['log'] .= 'Par     : ' . $this->cal->qtdImparPar($jogo)['par'] . " (7)\n";
            $result['log'] .= 'Soma    : ' . $this->cal->sumDezene($jogo) . " (166 - 220)\n";
            $result['log'] .= 'Total   : ' . count($jogo) . " (15)\n";
            $result['log'] .= 'Previsto: ' . implode("-", $jogo) . "\n";
        }
        return $result;
    }

    /**
     * Definir modo de teste.
     * A analise será realizada com os jogos antes do ultimo jogo para prever o ultimo jogo que já saiu.
     * 
     * @return void
     */
    public function setModeTest()
    {
        $this->test = true;
    }
}
