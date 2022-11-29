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
    static $cla = 0;

    /**
     * Gerar previsão de jogo
     * 
     * @return void
     */
    public static function run(): void
    {
        echo date("d/m/Y H:i:s") . " - Inicio \n";

        //$corect = [1, 2, 3, 4, 5, 7, 8, 11, 12, 13, 14, 15, 16, 20, 25]; // 2674

        // $dataset = (new Calculation())->getLastGames(400);
        // foreach (array_merge(self::$max, self::$med, self::$min) as $c_nx) {
        //    self::exPredict($dataset, $c_nx, $corect);
        // }

        // Gerar arquivos de treino
        // self::generateTraining(400, 5, 0);
        // self::generateTraining(400, 1000, 0);
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
        // self::generateTraining(400, 2201, 0);

        // Atualizar treinamento
        // self::updateTraining(400);
        // self::updateTraining(400, ['training2201.json']);
        // self::updateTraining(400, ['not_file']);

        // Últimos jogos 
        // $dataset = (new Calculation())->getLastGames(400);

        // Procurar pelos grupos e dezenas ideais
        // $groups = self::searchGrouping($dataset, 'training.json');
        // Helper::saveFile('predict.json', json_encode($groups));
        // print_r($groups);

        // Prever
        // foreach ($groups['grouping'] as $c_nx) {
        //     self::exPredict($dataset, $c_nx, $corect);
        // }

        // Procedimentos para novos jogos -------------------------------------->

        // # Gerar arquivos de treino
        self::generateTraining(400, 0, 0);

        // # Atualizar treinamento
        echo date("d/m/Y H:i:s") . " - Atualizando base de dados de treinamento \n";
        self::updateTraining(400, ['training0.json']);

        # Últimos jogos 
        echo date("d/m/Y H:i:s") . " - Obtendo lista dos últimos jogos \n";
        $dataset = (new Calculation())->getLastGames(400);

        # Gerar predict
        echo date("d/m/Y H:i:s") . " - Gerando previsão\n";
        $groups = self::searchGrouping($dataset, 'training.json');
        Helper::saveFile('predict.json', json_encode($groups));

        // # Selecionar dezenas mais previstas
        echo date("d/m/Y H:i:s") . " - Selecionando o agrupamento com maior probabilidade\n";
        $predict = json_decode(file_get_contents('predict.json'), true);
        $c_nx = array_key_first($predict['percent_hits_by_group']);

        //self::exPredict($dataset, $c_nx, $corect);   
        self::exPredict($dataset, $c_nx);   

        echo "\n" . date("d/m/Y H:i:s") . " - Fim \n";
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

            $i = 0;
            sort($data);
            foreach ($data as $dataGame) {
                $game = self::predict($dataset, self::$cla, $c_nx);
                $gameUnique = array_unique($game);
                $acertos = self::checkHits($dataGame['game_test'], $gameUnique);
                $countAcertos = count($acertos);

                if ($countAcertos >= 11) {
                    $name = "groups_with_{$countAcertos}_hits";
                    $result['hits_by_group'][$c_nx] = (empty($result['hits_by_group'][$c_nx]) ? 1 : $result['hits_by_group'][$c_nx] + 1);
                    $result[$name][$c_nx] = (empty($result[$name][$c_nx]) ? 1 : $result[$name][$c_nx] + 1);
                    $grouping[] = $c_nx;

                    foreach ($acertos as $dezena) {
                        $result['hits_by_tens'][$dezena] = (isset($result['hits_by_tens'][$dezena]) ? $result['hits_by_tens'][$dezena] + 1 : 1);
                        $result['hits_by_tens_group'][$c_nx][$dezena] = (isset($result['hits_by_tens_group'][$c_nx][$dezena]) ? $result['hits_by_tens_group'][$c_nx][$dezena] + 1 : 1);
                    }
                } else {
                    $result['error_by_group'][$c_nx] = (empty($result['error_by_group'][$c_nx]) ? 1 : $result['error_by_group'][$c_nx] + 1);
                }

                $i++;
                if ($i == 100) break;
            }

            arsort($result['hits_by_tens_group'][$c_nx]);
        }
        $result['grouping'] = array_values(array_unique($grouping));

        arsort($result['hits_by_tens']);
        arsort($result['hits_by_group']);
        arsort($result['error_by_group']);

        // $max_g = max($training['qtd_game_cnx'][$ngames]);
        foreach ($result['hits_by_group'] as $key => $value) {
            $total = $value + $result['error_by_group'][$key];
            // $diff = $max_g - $total;
            // $normalize = (100/$max_g) * ($diff);
            $percent_hits = (100 / $total) * $value;
            $result['percent_hits_by_group'][$key] = $percent_hits;  //($diff>0? $percent_hits - $normalize: $percent_hits);           
        }
        arsort($result['percent_hits_by_group']);

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
        $save_file = "training.json";
        self::$data = json_decode(file_get_contents($save_file), true);

        $path = "temp/";
        $dir = dir($path);

        while ($file_t = $dir->read()) {
            if (!in_array($file_t, ['.', '..']) && (empty($files) || in_array($file_t, $files))) {
                $json_t = json_decode(file_get_contents($path . $file_t), true);

                foreach ($json_t['training'][$ngames] as $c_nx => $data) {
                    if (isset(self::$data['training'][$ngames][$c_nx])) {
                        self::$data['training'][$ngames][$c_nx] = self::$data['training'][$ngames][$c_nx] + $json_t['training'][$ngames][$c_nx];
                    } else {
                        self::$data['training'][$ngames][$c_nx] = $json_t['training'][$ngames][$c_nx];
                    }
                }
            }
        }
        $dir->close();

        self::sevaTraining($save_file);
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
        echo date("d/m/Y H:i:s") . " - Inicio do treinamento \n";
        $cal = new Calculation();
        $save_file = "temp/training$preOffset.json";
        $groups = array_merge(self::$min, self::$med, self::$max);

        while ($preOffset >= $limitOffSet) {

            // $c_nx = 23;
            // while ($c_nx <= 100) {
            foreach ($groups as $c_nx) {

                $dataset = $cal->getLastGames($ngames + 1, $preOffset);
                $game_test = end($dataset);
                $key_last_game_test = array_key_last($dataset);
                unset($dataset[$key_last_game_test]);
                $game = self::predict($dataset, self::$cla, $c_nx);

                $gameUnique = array_unique($game);
                $acertos = self::checkHits($game_test, $gameUnique);

                if (count($acertos) >= 11) {
                    $concurso = $key_last_game_test + 1;
                    self::$data['training'][$ngames][$c_nx][$concurso]['contest'] = $concurso;
                    self::$data['training'][$ngames][$c_nx][$concurso]['game_test'] = $game_test;
                    self::$data['training'][$ngames][$c_nx][$concurso]['hits'] = count($acertos);
                    self::$data['training'][$ngames][$c_nx][$concurso]['correct_tens'] = $acertos;
                }
            }
            //$c_nx++;
            //}
            $preOffset--;
        }

        self::sevaTraining($save_file);
        self::$data = [];
        echo date("d/m/Y H:i:s") . " - Fim do treinamento \n";
    }

    /**
     * Prepara e salva dados de treinamento
     *
     * @param string $file Nome do arquivo onde deve salvar
     * @return void
     */
    public static function sevaTraining($file): void
    {
        $gmax = [];
        $gmin = [];
        $arr_ngames = array_keys(self::$data['training']);

        foreach ($arr_ngames as $ngames) {
            foreach (self::$data['training'][$ngames] as $c_nx => $data) {
                $gmin[$c_nx] = min($data)['contest'];
                $gmax[$c_nx] = max($data)['contest'];

                self::$data['unique'][$ngames][] = $c_nx;
                self::$data['qtd_game_cnx'][$ngames][$c_nx] = count(self::$data['training'][$ngames][$c_nx]);
            }

            arsort(self::$data['qtd_game_cnx'][$ngames]);
            self::$data['unique'][$ngames] = array_unique(self::$data['unique'][$ngames]);

            sort(self::$data['unique'][$ngames]);
            self::$data['unique'][$ngames] = array_values(self::$data['unique'][$ngames]);

            self::$data['info']['gmin'] = min($gmin);
            self::$data['info']['gmax'] = max($gmax);
        }

        Helper::saveFile($file, json_encode(self::$data));
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
            $game = self::predict($dataset, self::$cla, $c_nx);
            $gameUnique = array_unique($game);
            $acertos = self::checkHits($game_test, $gameUnique);

            if (count($acertos) >= 11) {
                echo Helper::title('Previsão de Números')
                    . "\nPrevisão do Jogo: " . array_key_last($dataset) + 2
                    . "\nJogos: " . count($dataset)
                    . "\nGrupo: " . $c_nx
                    . "\nCorreto: " . implode('-', $game_test)
                    . "\nPrevisto: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')'
                    . "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')'
                    . "\n";
            }
        } else {
            $game = self::predict($dataset, self::$cla, $c_nx);
            $gameUnique = array_unique($game);

            echo Helper::title('Previsão de Números')
                . "\nPrevisão do Jogo: " . array_key_last($dataset) + 2
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
     * @param array $gameTest
     * @param array $jogo
     */
    public static function checkHits(array $gameTest, array $jogo): array
    {
        return array_intersect($gameTest, $jogo);
    }
}
