<?php

namespace Src;

use Src\Calculation;
use Phpml\Dataset\CsvDataset;
use Phpml\Classification\KNearestNeighbors;


class Computer
{

    public static $file_dataset = 'dataset.csv';

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function run($test = false)
    {
        // $dataset = new CsvDataset('dataset.csv', 15, true);
        // $samples = $dataset->getSamples();
        // $labels = $dataset->getTargets();
        print_r(self::play($test, 2500, 0, true));
    }

    /**
     * Undocumented function
     *
     * @param bool $test Modo de teste
     * @param integer $ngames Número de jogos
     * @param integer $limitAct Limite de aceite
     * @param boolean $log Exibir logs
     * @return array
     */
    public static function play($test = false, $ngames = 9, $limitAct = 0, $log = false)
    {
        $cal = new Calculation();
        $dataset = $cal->getLastGames($ngames);

        if ($test) {

            $game_test = end($dataset);
            unset($dataset[array_key_last($dataset)]);
            $game = self::train($dataset, true);
            $gameUnique = array_unique($game);
            $acertos = $cal->checkHits($game_test, $game);

            if (count($acertos) >= $limitAct && $log == true) {
                echo "\nJogos: " . $ngames;
                echo "\nPrevisto: " . implode('-', $game);
                echo "\nCorreto: " . implode('-', $game_test);
                echo "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')';
                echo "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')';
                echo "\n";
            }
        } else {
            $game = self::train($dataset);
            $gameUnique = array_unique($game);
            if ($log == true) {
                echo "\nPrevisto: " . implode('-', $game);
                echo "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')';
                echo "\n";
            }
        }

        return $gameUnique;
    }

    /**
     * Treinar e prever jogo
     *
     * @return array
     */
    public static function train($dataset)
    {
        $nx = [];
        $i = 0;
        $samples = [];
        foreach ($dataset as $balls) {
            if (isset($nx[0]) && count($nx[0]) == 6) {

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

        $game = [];
        $classifier = new KNearestNeighbors(7);
        foreach ($samples as $key => $sample) {
            $classifier->train($sample, $labels[$key]);
            $game[$key] = $classifier->predict(end($sample));
        }

        return $game;
    }
}
