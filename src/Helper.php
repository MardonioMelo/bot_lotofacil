<?php

namespace Src;

class Helper
{
    /**
     * Titulo para logos
     *
     * @param string $desc Titulo do log
     * @return string
     */
    public static function title(string $desc): string
    {
        $title = "\n------------------------------------> \n";
        $title .= " $desc\n";
        $title .= "------------------------------------> \n";
        return $title;
    }

    /**
     *  Verificar treinamento
     *
     * @param array $training array gerada com método $this->trainingMargin()
     * @param int $modo Modo de exibição dos dados
     * @return void
     */
    public static function showTraining(array $training = [], int $modo = 1, $title="Margens")
    {
        if($modo == 1){
            echo "\n". str_pad($title, 32, '-', STR_PAD_RIGHT). "|";
            echo "\nAnálise-------------|Mín|Máx|Méd \n";
            foreach ($training as $name => $arrTrain) {
                echo str_pad($name, 20, '-', STR_PAD_RIGHT);
                echo "|";
                echo str_pad($arrTrain['min'], 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['max'], 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['med'], 3, ' ', STR_PAD_LEFT);
                echo "\n";
            }
            echo "--------------------------------| \n";

        }elseif($modo == 2){
            echo "\n". str_pad($title, 26, '-', STR_PAD_RIGHT). "|";
            echo "\nAnálise-------------|Total \n";
            foreach ($training as $name => $arrTrain) {
                echo str_pad($name, 20, '-', STR_PAD_RIGHT);
                echo "|";
                echo str_pad($arrTrain['analysis'], 3, ' ', STR_PAD_LEFT);             
                echo "\n";
            }
            echo "--------------------------| \n";
        }       
    }

}
