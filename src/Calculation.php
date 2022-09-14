<?php

namespace Src;

class Calculation
{

    private $dataset;
    private $file_dataset;
    private $numPri;

    /**
     * Construção
     * 
     */
    public function __construct()
    {
        $this->numPri = [2, 3, 5, 7, 11, 13, 17, 19, 23];
        $this->file_dataset = "./dataset.json";

        if (!file_exists($this->file_dataset)) {
            $this->updateDataset();
        }
        $this->setDataset();
    }

    /**
     * Resgatar os números primos
     *
     * @return void
     */
    public function getNumPri()
    {
        return $this->numPri;
    }

    /**
     * Verificar se um jogo é inédito
     *
     * @param array $getDataSetString
     * @param array $game
     * @param bool $test
     * @return int Retorna 0 se for inédito ou o número do sorteio se ja saiu 
     */
    public function unprecedented(array $getDataSetString, array $game, bool $test = false): int
    {
        $result = 0;
        $game = trim(implode("-", $game));
        if($test) array_pop($getDataSetString);

        if(in_array($game, $getDataSetString)){
            $result = array_flip($getDataSetString)[$game];
        }

        return $result;
    }

    /**
     * Obter todos os concursos em um array onde cada valor é um jogo em formato string
     *
     * @return array
     */
    public function getDataSetString(): array
    {
        $result = [];
        foreach ($this->dataset as $key => $val) {
            $result[] = implode('-', $val);
        }
        return $result;
    }

    /**
     * Verificar o valor máximo e minimo de cada bola
     *
     * @param array $last_games
     * @return array
     */
    public function chackMaxMin(array $last_games): array
    {
        $data = [];

        foreach ($last_games as $jogo) {
            foreach ($jogo as $key => $dezena) {
                $data['Bola' . ($key + 1)][] = $dezena;
            }
        }

        $max_min = [];
        foreach ($data as $key => $item) {
            $max_min[$key]['min'] = min($item);
            $max_min[$key]['max'] = max($item);
        }
        unset($data);

        return  $max_min;
    }

    /**
     * Contar a frequência de cada número
     *
     * @param array $last_games
     * @return array
     */
    public function countFrequency(array $last_games): array
    {
        for ($i = 1; $i <= 25; $i++) {
            $frequency[$i] = 0;
        }

        foreach ($last_games as $line) {

            foreach ($line as $item) {
                $frequency[(int)$item] += 1;
            }
        }

        $print = [];

        arsort($frequency);
        foreach ($frequency as $key => $item) {
            $print[$key] = $item;
        }

        return $print;
    }

    /**
     * Quantidade de números imperes e pares no jogo
     *
     * @param array $jogo
     * @return array
     */
    public function qtdImparPar(array $jogo): array
    {
        $qtd['par'] = 0;
        $qtd['impar'] = 0;
        foreach ($jogo as $item) {
            is_int((int)$item / 2) ? $qtd['par'] += 1 : $qtd['impar'] += 1;
        }

        return $qtd;
    }

    /**
     *  Números mais atrasados em ordem decrescente de atraso
     *
     * @param array $last_games
     * @return array
     */
    public function laterNumbers(array $last_games): array
    {
        for ($i = 1; $i <= 25; $i++) {
            $dezena['count'][$i] = 0;
            $dezena['stop'][$i] = false;
        }

        krsort($last_games);

        foreach ($last_games as $line) {
            foreach ($dezena['stop'] as $key => $item) {

                if (in_array(str_pad($key, 2, '0', STR_PAD_LEFT), $line)) {
                    $dezena['stop'][(int)$key] = true;
                } else if ($dezena['stop'][(int)$key] == false) {
                    $dezena['count'][(int)$key] += 1;
                }
            }
        }
        arsort($dezena['count']);

        return array_filter($dezena['count']);
    }

    /**
     * Soma das dezenas 166-220
     *
     * @return void
     */
    public function sumDezene($jogo)
    {
        return array_sum($jogo);
    }

    /**
     * Quadro com os 25 resultados
     *
     * @return void
     */
    public function withThe25()
    {
        $print = "\nQuadro com os 25 resultados:\n";

        for ($i = 1; $i <= 25; $i++) {
            $table_ref[$i] = $i;
        }

        foreach ($this->dataset as $line) {

            $str = "";
            foreach ($table_ref as $item_ref) {

                if (in_array($item_ref, $line)) {
                    $num = str_pad($item_ref, 2, '0', STR_PAD_LEFT);
                    $str .= " 0 ";
                } else {
                    $str .= "   ";
                }
            }

            $print .= "\n {$str}";
        }

        $save_file = fopen("./dataset_img.txt", "w+");
        fwrite($save_file, $print);
        fclose($save_file);

        print_r("\nOs dados foram salvos no arquivo dataset_img\n");
    }

    /**
     * Atualizar base de dados
     *
     * @return void
     */
    public function updateDataset()
    {
        print_r("\nConsultando dados da API...");

        $last = file_get_contents('https://loteriascaixa-api.herokuapp.com/api/lotofacil');

        print_r("\nSalvando os dados consultados...");

        $save_file = fopen($this->file_dataset, "w+");
        fwrite($save_file, $last);
        fclose($save_file);

        print_r("\nBase de dados atualizada!\n");
    }

    /**
     * Carregar histórico de jogos na memória
     *
     * @return void
     */
    public function setDataset()
    {
        $json = json_decode(file_get_contents($this->file_dataset), true);
        sort($json);
        $this->dataset = array_map(function ($arr) {
            return array_map(function ($v) {
                return (int) $v;
            }, $arr);
        }, array_column($json, 'dezenas'));
    }

    /**
     * Obter uma fração dos últimos concursos
     *
     * @param integer $qtd
     * @return array
     */
    public function getLastGames(int $limit): array
    {
        return $limit >= count($this->dataset) ?
            $this->dataset :
            array_slice($this->dataset, count($this->dataset) - $limit, $limit, true);
    }

    /**
     * Verificar acertos referente ao próximo jogo de teste
     *
     * @param array $endGameTest
     * @param array $jogo
     */
    public function checkHits(array $endGameTest, array $jogo): array
    {
        $hits = [];
        foreach ($endGameTest as $item) {
            if (in_array($item, $jogo)) {
                $hits[] = $item;
            }
        }
        return $hits;
    }

    /**
     * yes_15_exist - Verificar dezenas do concurso anterior que contem no jogo em atual
     * not_10_exist - Verificar quais das 10 dezenas que não saiu no concurso anterior e que existem no jogo atual
     * not_prim_exist - Verificar números primos que não saiu no ultimo concurso e existem no jogo atual
     * yes_prim_exist - Verificar números primos que saiu no ultimo concurso e existem no jogo atual
     * prim_exist - Verificar números primos existentes no jogo atual
     *
     * @param array $endGame
     * @param array $jogo
     * @return array
     */
    public function checkPreviousExist(array $endGame, array $game): array
    {
        $result = [];
        $range = range(1, 25);
        $result['yes_15_exist'] = [];
        $result['not_10_exist'] = [];
        $result['yes_prim_exist'] = [];
        $result['not_prim_exist'] = [];
        $result['prim_exist'] = [];

        foreach ($range as $num) {
            if (in_array($num, $game)) {

                if (in_array($num, $endGame)) {
                    $result['yes_15_exist'][] = $num;
                } else {
                    $result['not_10_exist'][] = $num;
                }

                if (in_array($num, $this->numPri) && in_array($num, $endGame)) {
                    $result['yes_prim_exist'][] = $num;
                }

                if (in_array($num, $this->numPri) && !in_array($num, $endGame)) {
                    $result['not_prim_exist'][] = $num;
                }

                if (in_array($num, $this->numPri)) {
                    $result['prim_exist'][] = $num;
                }
            }
        }

        return $result;
    }

    /**
     * Verificar se o pai e ao menos um dos filhos existem no jogo
     *
     * @param array $game
     * @param int $father
     * @param array $child
     * @return bool True se estiver OK, false se o pai existe no jogo e os filhos não.
     */
    public function checkParent(array $game, int $father, array $child): bool
    {
        $result = true;
        if (in_array($father, $game)) {
            $result = false;
            foreach ($child as $num) {
                if (in_array($num, $game)) $result = true;
            }
        }
        return $result;
    }

    /**
     * Verificar quais números dos mais atrasados que estão no jogo
     *
     * @param array $laterNumbers Array obtida com $this->cal->laterNumbers($last_games)
     * @param array $game
     * @param int $qtdNum Quantidade dos mais atrasados a verificar, a contar do primeiro atrasado.
     * @return array Array com os números atrasados como chave e quantidade de repetição como valor
     */
    public function checkLaterNumInGame(array $laterNumbers, array $game, int $qtdNum = 10): array
    {
        $result = [];
        $laterNumbers = array_slice($laterNumbers, 0, $qtdNum);
        foreach ($laterNumbers as $num => $qtd) {
            if (in_array($num, $game)) $result[$num] = $qtd;
        }       
        return $result;
    }

    /**
     * Verificar quais números das mais sorteados que estão no jogo
     *
     * @param array $countFrequency Array obtida com $this->cal->countFrequency($last_games)
     * @param array $game
     * @param int $qtdFrequency Quantidade dos números das mais sorteados a verificar, a contar do primeiro com mais franqueia.
     * @return array Array com os números atrasados como chave e quantidade de repetição como valor
     */
    public function checkFrequencyInGame(array $countFrequency, array $game, int $qtdNum = 15): array
    {
        $result = [];
        $countFrequency = array_slice($countFrequency, 0, $qtdNum);
        foreach ($countFrequency as $num => $qtd) {
            if (in_array($num, $game)) $result[$num] = $qtd;
        }       
        return $result;
    }

    /**
     * Verificar se as bolas do jogo estão dentro do máximo e mínimo para cada bola
     *
     * @param array $last_games Jogos anteriores para verificar o máximo e mínimo
     * @param array $jogo Jogo que será avaliado
     * @return array
     */
    public function checkMaxMinGame($last_games, $jogo): array
    {
        $checkMaxMin = $this->chackMaxMin($last_games);
        $result = [];
        $i = 0;
        foreach ($jogo as $bola) {
            $i++;
            if ($bola < $checkMaxMin['Bola' . $i]['min'] && $bola > $checkMaxMin['Bola' . $i]['max']) {
                $result['Bola' . $i] = $bola;
            }
        }
        return $result;
    }

    /**
     * Verificar o padrão a cada 5 dezenas montando uma sequencia de 5 números com as soma a cada 5 dezenas.
     * Contar quantas foram marcadas e calcular o porcentagem de repetição de cada sequencia
     *
     * @param array $last_games Últimos jogos a serem analisados
     * @param int $n_lasts Quantidade dos que mais se repetem a ser retornado
     * @return array
     */
    public function checkPattern5balls($last_games, $n_last = 5): array
    {
        $result = [];
        foreach ($last_games as $game) {
            $num = [];
            $num[1] = 0;
            $num[2] = 0;
            $num[3] = 0;
            $num[4] = 0;
            $num[5] = 0;

            foreach ($game as $boll) {
                if ($boll <= 5) $num[1]++;
                if ($boll >= 6 && $boll <= 10) $num[2]++;
                if ($boll >= 11 && $boll <= 15) $num[3]++;
                if ($boll >= 16 && $boll <= 20) $num[4]++;
                if ($boll >= 21) $num[5]++;
            }

            $num = implode('', $num);
            if (isset($result['n_' . $num])) {
                $result['n_' . $num]++;
            } else {
                $result['n_' . $num] = 1;
            }
        }
        arsort($result);

        if ($n_last > 0) $result = array_slice($result, 0, $n_last);

        //calcular porcentagem
        $qtd_games = count($last_games);
        foreach ($result as $key => $val) {
            $percent = (100 / $qtd_games) * $val;
            $result[$key] = "$val vzs | $percent %";
        }

        return $result;
    }

    /**
     * Obter wordlist de todos os possíveis jogos
     *
     * @return array
     */
    public function getWordlist(): array
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
     *  Verificar treinamento
     *
     * @param array $training array gerada com método $this->checkAnalysis()
     * @return void
     */
    public function checkTraining(array $training)
    {        
        echo "---------Nome--------|Máx|Mín \n";

        foreach ($training as $name => $arrTrain) {
            echo str_pad($name, 20, '-', STR_PAD_RIGHT);
            echo "|"; 
            echo str_pad(max($arrTrain), 3, ' ', STR_PAD_LEFT);
            echo "|"; 
            echo str_pad(min($arrTrain), 3, ' ', STR_PAD_LEFT);
            echo "\n"; 
        }      
    }
}
