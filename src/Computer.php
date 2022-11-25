<?php

namespace Src;

use Src\Calculation;
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\Classification\KNearestNeighbors;

class Computer
{

    static $data = [];
    static $max = [80, 61, 60, 59, 57, 56, 52, 49, 47, 46, 45, 44];
    static $med = [43, 41, 40, 38, 37, 36, 33, 31, 29, 28];
    static $min = [25, 23];

    /**
     * Gerar previsão de jogo
     * 
     * @return void
     */
    public static function run(): void
    {
        echo date("d/m/Y H:i:s") . " - Inicio \n";

        // $nGames = 400;
        // foreach (array_merge(self::$max, self::$med, self::$min) as $val) {          
        //     self::exPredict($dataset, $c_nx, [3,6,7,8,9,11,12,13,15,16,18,19,20,21,25]);
        // }

        // Gerar arquivos de treino
        // self::generateTraining(400, 1100, 1001);
        // self::generateTraining(400, 1200, 1101);
        // self::generateTraining(400, 1300, 1201);
        // self::generateTraining(400, 1400, 1301);
        // self::generateTraining(400, 1500, 1401);
        // self::generateTraining(400, 1600, 1501);
        // self::generateTraining(400, 1700, 1601);
        // self::generateTraining(400, 1800, 1701);
        // self::generateTraining(400, 1900, 1801);
        // self::generateTraining(400, 2000, 1901);
        // self::generateTraining(400, 2100, 2001);
        // self::generateTraining(400, 2200, 2101);
        // self::generateTraining(400, 5, 0);

        // Atualizar treinamento
        //self::updateTraining(400, ['training5.json']);

        // Últimos jogos 
        $dataset = (new Calculation())->getLastGames(400);

        // Procurar pelos grupos ideais
        $groups = self::searchGrouping($dataset, 'training.json');
        print_r($groups);        

        // Prever
        foreach ($groups['grouping'] as $c_nx) {
            self::exPredict($dataset, $c_nx, [1,2,5,7,8,9,10,11,12,13,18,20,21,22,25]);
        }

        echo date("d/m/Y H:i:s") . "\n - Fim \n";
    }

    /**
     * Procurar pelo agrupamento correto
     *
     * @param array $dataset Últimos jogos - Ex.: (new Calculation())->getLastGames(400)
     * @return array Array com os números de agrupamentos eleitos
     */
    public static function searchGrouping($dataset, $name_file = 'training.json'): array
    {
        $result = [];
        $grouping = [];
        $ngames = count($dataset);
        $training = json_decode(file_get_contents($name_file), true);
        foreach ($training['training'][$ngames] as $c_nx => $data) {

            foreach ($data as $dataGame) {
                $game = self::predict($dataset, 1, $c_nx);
                $gameUnique = array_unique($game);
                $acertos = self::checkHits($dataGame['game_test'], $gameUnique);
                $countAcertos = count($acertos);

                if ($countAcertos >= 11) {
                    $name = $countAcertos . '_acertos';
                    $result['total_acertos'][$c_nx] = (empty($result['total_acertos'][$c_nx]) ? 1 : $result['total_acertos'][$c_nx] + 1);
                    $result[$name][$c_nx] = (empty($result[$name][$c_nx]) ? 1 : $result[$name][$c_nx] + 1);
                    $grouping[] = $c_nx;
                }
            }
        }
        $result['grouping'] = array_unique($grouping);
        $result = array_map(function ($v) {
            arsort($v);
            return $v;
        }, $result);

        return $result;
    }

    /**
     * Atualizar treinamento
     *
     * @param int $ngames
     * @param array $files Arquivos selecionados para importar na atualização, se for vazio, todos os aquivos da pasta temp serão importados
     * @return void
     */
    public static function updateTraining($ngames = 400, $files = [])
    {
        $file = "training.json";
        $json = json_decode(file_get_contents($file), true);

        $path = "temp/";
        $dir = dir($path);

        while ($file_t = $dir->read()) {
            if (!in_array($file_t, ['.', '..']) && (empty($files) || in_array($file_t, $files))) {
                $json_t = json_decode(file_get_contents($path . $file_t), true);

                foreach ($json_t['training'][$ngames] as $c_nx => $data) {
                    if(isset($json['training'][$ngames][$c_nx])){
                        $json['training'][$ngames][$c_nx] = $json['training'][$ngames][$c_nx] + $json_t['training'][$ngames][$c_nx];         
                    }else{
                        $json['training'][$ngames][$c_nx] = $json_t['training'][$ngames][$c_nx];         
                    }
                    
                    $json['qtd_game_cnx'][$ngames][$c_nx] = count($json['training'][$ngames][$c_nx]);
                }
            }
        }
        $dir->close();

        $gmax = [];
        $gmin = [];
        foreach ($json['training'][$ngames] as $c_nx => $data) {
            $gmax[$c_nx] = max($data)['contest'];
            $gmin[$c_nx] = min($data)['contest'];
        }

        $json['info']['gmin'] = min($gmin);
        $json['info']['gmax'] = max($gmax);

        Helper::saveFile($file, json_encode($json));
        self::$data = [];
    }

    /**
     * Gerar dados do trinamento previsão de jogo
     *
     * @param int $ngames Número de jogos que deve avaliar
     * @param int $preOffset Inicio do offset
     * @param int $limitOffSet Remove últimos registros antes de aplicar o limite
     * @return void
     */
    public static function generateTraining($ngames = 400, $preOffset = 1, $limitOffSet = 0): void
    {
        echo date("d/m/Y H:i:s") . " - Inicio da geração do treinamento de $preOffset jogos \n";
        $cal = new Calculation();
        $name_file = "temp/training$preOffset.json";
        $groups = array_merge(self::$min, self::$med, self::$max);

        while ($preOffset >= $limitOffSet) {

            // $c_nx = 23;
            // while ($c_nx <= 100) {
            foreach ($groups as $c_nx) {

                $dataset = $cal->getLastGames($ngames + 1, $preOffset);
                $game_test = end($dataset);
                $key_last_game_test = array_key_last($dataset);
                unset($dataset[$key_last_game_test]);
                $game = self::predict($dataset, 1, $c_nx);

                $gameUnique = array_unique($game);
                $acertos = self::checkHits($game_test, $gameUnique);

                if (count($acertos) >= 11) {
                    $concurso = $key_last_game_test + 1;
                    self::$data['training'][$ngames][$c_nx][$concurso]['contest'] = $concurso;
                    self::$data['training'][$ngames][$c_nx][$concurso]['game_test'] = $game_test;
                    self::$data['training'][$ngames][$c_nx][$concurso]['hits'] = count($acertos);
                    self::$data['training'][$ngames][$c_nx][$concurso]['correct_tens'] = $acertos;
                    self::$data['qtd_game_cnx'][$ngames][$c_nx] = count(self::$data['training'][$ngames][$c_nx]);
                    self::$data['unique'][$ngames][] = $c_nx;
                    self::$data['unique'][$ngames] = array_unique(self::$data['unique'][$ngames]);
                    arsort(self::$data['unique'][$ngames]);
                }
            }
            //$c_nx++;
            //}
            $preOffset--;
        }

        Helper::saveFile($name_file, json_encode(self::$data));
        self::$data = [];
        echo date("d/m/Y H:i:s") . " - Fim da geração do treinamento \n";
    }

    /**
     * Gerar previsão de jogo
     *
     * @param array $dataset Últimos jogos - Ex.: (new Calculation())->getLastGames(400)
     * @param int $c_nx Agrupamento - Quantidade de jogos que deve agrupar
     * @param array $game_test Um jogo para testar os acertos - As dezenas que saíram no próximo jogo e que não estão no dataset.json
     * @return array
     */
    public static function exPredict($dataset, $c_nx, $game_test = []): array
    {
        if (count($game_test) >= 15) {
            $game = self::predict($dataset, 1, $c_nx);
            $gameUnique = array_unique($game);
            $acertos = self::checkHits($game_test, $gameUnique);

            if (count($acertos) >= 11) {
                echo Helper::title('Previsão de Números')
                    . "\nJogos: " . count($dataset)
                    . "\nGrupo: " . $c_nx
                    . "\nCorreto: " . implode('-', $game_test)
                    . "\nPrevisto: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')'
                    . "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')'
                    . "\n";
            }
        } else {
            $game = self::predict($dataset, 1, $c_nx);
            $gameUnique = array_unique($game);

            echo Helper::title('Previsão de Números')
                . "\nJogos: " . count($dataset)
                . "\nGrupo: " . $c_nx
                . "\nPrevisto: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')'
                . "\n";
        }

        return $gameUnique;
    }

    /**
     * Treinar e prever jogo
     * @param array $dataset dados para treino
     * @param int $cla Escolha do classificador, 
     * 0 = KNearestNeighbors, 
     * 1 = SVC(Kernel::LINEAR), 
     * 2 = new SVC(Kernel::RBF), 
     * 3 = new SVC(Kernel::SIGMOID
     * @param int $c_nx Quantidade de jogos que deve agrupar
     * @return array
     */
    public static function predict($dataset, $cla = 0, $c_nx = null): array
    {
        $c_nx_d = [3, 10, 3, 8];
        $c_nx = (!$c_nx ? $c_nx_d[$cla] : $c_nx);
        $nx = [];
        $i = 0;
        $samples = [];
        foreach ($dataset as $balls) {
            if (isset($nx[0]) && count($nx[0]) == $c_nx) {

                foreach ($balls as $key => $ball) {
                    $samples[$key][] = $nx[$key];
                    $labels[$key][] = $ball;
                }
                $i++;
                $nx = [];
            }

            //Agrupar 3 e nomear o próximo número
            foreach ($balls as $key => $ball) {
                $nx[$key][] = $ball;
            }
        }

        if ($cla == 0) {
            $classifier = new KNearestNeighbors(1);
        } elseif ($cla == 1) {
            $classifier = new SVC(Kernel::LINEAR, 1, 3, 6);
        } elseif ($cla == 2) {
            $classifier = new SVC(Kernel::RBF, 1, 3, 6);
        } else {
            $classifier = new SVC(Kernel::SIGMOID, 1, 3, 6);
        }

        $game = [];
        foreach ($samples as $key => $sample) {
            $classifier->train($sample, $labels[$key]);
            $game[$key] = $classifier->predict(end($sample));
        }

        return $game;
    }

    /**
     * Verificar acertos referente ao próximo jogo de teste
     *
     * @param array $endGameTest
     * @param array $jogo
     */
    public static function checkHits(array $endGameTest, array $jogo): array
    {
        return array_intersect($endGameTest, $jogo);
    }
}
