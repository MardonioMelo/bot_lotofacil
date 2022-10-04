<?php

namespace Src;

class Calculation
{

    private $dataset;
    private $file_dataset;
    private $numPri;
    private $training;
    private $prime_20;

    /**
     * Construção
     * 
     */
    public function __construct()
    {
        $this->numPri = [2, 3, 5, 7, 11, 13, 17, 19, 23];
        $this->prime_20 = [1, 2, 3, 4, 6, 9, 10, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24, 25];
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
        if ($test) array_pop($getDataSetString);

        if (in_array($game, $getDataSetString)) {
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
     * @param string $file Nome do arquivo onde deverá ser salvo os dados
     * @param array $games
     * @return void
     */
    public function withThe25($file = "img25.txt", $games = [])
    {
        $content = [];
        $table_ref = range(1, 25);
        $div = range(0, 25, 5);
        $games = empty($games) ? $this->dataset : $games;

        foreach ($games as $line) {
            $str = [];
            foreach ($table_ref as $item_ref) {
                if (in_array($item_ref, $line)) {
                    $str[] = "1";
                } else {
                    $str[] = "0";
                }
                if (in_array($item_ref, $div)) $str[] = '|';
            }
            $content[] = implode("", $str);
        }

        Helper::saveFile("./$file", implode("\n", $content));
        print_r("\nOs dados foram salvos no arquivo $file\n");
    }

    /**
     * Gerar jogo com a regra 3x3
     *
     * @param string $test Treu para modo de teste
     * @return void
     */
    public function game3x3($test = false)
    {
        $name_file = 'game3x3.txt';
        $arr_base = [];
        $last_games = $this->dataset;
        //$last_games = $this->getLastGames(1500);

        $this->withThe25($name_file, $last_games);
        $file = fopen($name_file, "r");

        while (!feof($file)) {
            $line = fgets($file);
            $arr_base[] = explode('|', $line);
        }
        fclose($file);

        if ($test) {
            $prev3x3 = end($arr_base);
            unset($arr_base[array_key_last($arr_base)]);
        }

        $matriz = [];
        $i = 0;
        foreach ($arr_base as $key => $arr_part) {
            $i++;
            if ($i >= 3) {
                foreach ($arr_part as $key_part => $part) {
                    $x1 = $arr_base[$key - 2][$key_part];
                    $x2 = $arr_base[$key - 1][$key_part];
                    $matriz[$x1 . '_' . $x2][$part] = (empty($matriz[$x1 . '_' . $x2][$part]) ? 1 : $matriz[$x1 . '_' . $x2][$part] + 1);
                }
            }
        }

        //variáveis dos últimos jogos
        $prevx1 = $arr_base[array_key_last($arr_base) - 1];
        $prevx2 = $arr_base[array_key_last($arr_base)];

        //Previsão
        $prevx3 = [];
        for ($i = 0; $i < count($prevx1); $i++) {
            $key = trim($prevx1[$i] . '_' . $prevx2[$i]);
            if (!empty($matriz[$key]) && $key != '_') {
                $statistic = $matriz[$key];
                $prevx3[$i] = array_search(max($statistic), $statistic);
                print_r($statistic);
            } else {
                $prevx3[$i] = '     ';
            }
        }



        echo "\nPrevisto: " . implode(' | ', $prevx3);
        if ($test) {
            echo "\nCorreto : " . implode(' | ', $prev3x3);

            //Acertos
            $arr_prev3x3 = str_split(implode('', $prev3x3));
            $arr_prevx3 = str_split(implode('', $prevx3));
            $i = 0;
            $qtd_num = 0;
            foreach ($arr_prev3x3 as $key => $val) {
                if (!empty($arr_prevx3[$key])) {
                    if ($val == $arr_prevx3[$key] && $arr_prevx3[$key] > 0) {
                        $i++;
                    }
                    if ($arr_prevx3[$key] > 0) {
                        $qtd_num++;
                    }
                }
            }

            echo "\nQtd. N. : $qtd_num";
            echo "\nAcertos : $i";
            echo "\n";
        }
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
        Helper::saveFile($this->file_dataset, $last);
        print_r("\nBase de dados atualizada!");
        $json = json_decode($last, true);
        sort($json);
        $endGame = end($json);
        print_r("\nUltimo Concurso: {$endGame['concurso']} - {$endGame['data']}\n");
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
     * Consultar quantidade total de concursos
     *
     * @param integer $qtd
     * @return array
     */
    public function getQtdTotalGames(): int
    {
        return count($this->dataset);
    }

    /**
     * Consultar todos os jogos dos concursos anteriores
     *
     * @param boolean $test
     * @return array
     */
    public function allPreviousGames(bool $test = false): array
    {
        $all = $this->dataset;
        if ($test) array_pop($all);
        return $all;
    }

    /**
     * Verificar acertos referente ao próximo jogo de teste
     *
     * @param array $endGameTest
     * @param array $jogo
     */
    public function checkHits(array $endGameTest, array $jogo): array
    {
        return array_intersect($endGameTest, $jogo);
    }

    /**
     * yes_15_exist - Verificar dezenas do concurso anterior que contem no jogo em atual
     * not_10_exist - Verificar quais das 10 dezenas que não saiu no concurso anterior e que existem no jogo atual
     * not_prim_exist - Verificar números primos que não saiu no ultimo concurso e existem no jogo atual
     * yes_prim_exist - Verificar números primos que saiu no ultimo concurso e existem no jogo atual
     * prim_exist - Verificar números primos existentes no jogo atual
     * prime_20 - Verificar qual números estão no jogo de 20 números que mais pontuou
     * num_exist - Verificar se existem outros números específicos
     *
     * @param array $endGame
     * @param array $jogo
     * @param array $exist
     * @return array
     */
    public function checkPreviousExist(array $endGame, array $game, array $exist = []): array
    {
        $result = [];
        $range = range(1, 25);
        $result['yes_15_exist'] = [];
        $result['not_10_exist'] = [];
        $result['yes_prim_exist'] = [];
        $result['not_prim_exist'] = [];
        $result['prim_exist'] = [];
        $result['prime_20'] = [];
        $result['num_exist'] = [];
        $result['num_sequence'] = [];

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

                if (in_array($num, $this->prime_20)) {
                    $result['prime_20'][] = $num;
                }

                if (in_array($num, $exist)) {
                    $result['num_exist'][] = $num;
                }

                if (isset($lastN) && $num == ($lastN + 1)) {
                    $k = array_key_last($result['num_sequence']);
                    $result['num_sequence'][$k] += 1;
                } else {
                    $k = array_key_last($result['num_sequence']);
                    $result['num_sequence'][$k + 1] = 1;
                }
                $lastN = $num;
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
    public function checkLaterNumInGame(array $laterNumbers, array $game, int $qtdNum = 4): array
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
     * @param int $qtdNum Quantidade dos números das mais sorteados a verificar, a contar do primeiro com mais frequência.
     * @return array Array com os números atrasados como chave e quantidade de repetição como valor
     */
    public function checkFrequencyInGame(array $countFrequency, array $game, int $qtdNum = 4): array
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
     * Obter o range mínimo e máximo quem que os últimos jogos saiu referente a wordlist
     *
     * @param int $limit Limite de jogos
     * @return array
     */
    public function getMarginList($limit = 2600): array
    {
        $file = 'position.txt';

        if (file_exists($file)) {
            $arr = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            unset($arr[0]);
        }

        $positions = ($limit >= count($arr) ? $arr : array_slice($arr, count($arr) - $limit, $limit, true));

        $result = [];
        foreach ($positions as $key => $val) {
            $result[] = trim(explode("|", $val)[1]);
        }

        return [
            "min" => min($result),
            "max" => max($result),
            "diff" => max($result) - min($result)
        ];
    }

    /**
     * Verificar margens para analises posterior 
     *
     * @param array $jogos para verificara
     * @return void
     */
    public function checkMargin(array $games): void
    {
        $laterNumbers = $this->laterNumbers($games);
        $countFrequency = $this->countFrequency($games);
        $this->training = $training = [];

        foreach ($games as $key => $jogo) {

            if (!empty($games[$key - 1])) {

                # A soma das dezenas
                $sumDezene = $this->sumDezene($jogo);

                $qtdImparPar = $this->qtdImparPar($jogo);
                # Dezenas impares
                $qtdPar = $qtdImparPar['par'];
                # Dezenas pares
                $qtdImpar = $qtdImparPar['impar'];

                $checkPreviousExist = $this->checkPreviousExist($games[$key - 1], $jogo);
                # Dezenas que saiu no concurso anterior
                $yes_15_exist = count($checkPreviousExist['yes_15_exist']);
                # Dezenas das 10 que não saiu no concurso anterior
                $not_10_exist = count($checkPreviousExist['not_10_exist']);
                # Números primos que saiu no último concurso
                $yes_prim_exist = count($checkPreviousExist['yes_prim_exist']);
                # Números primos que não saiu no ultimo concurso
                $not_prim_exist = count($checkPreviousExist['not_prim_exist']);
                # Números que estão no jogo de 20 números q mais pontuou
                $prime_20 = count($checkPreviousExist['prime_20']);

                # Números sequenciais
                $num_sequence = max($checkPreviousExist['num_sequence']);

                # Números primos
                $prim_exist = count($checkPreviousExist['prim_exist']);

                # Dezenas das 5 mais atrasadas
                $checkLaterNumInGame = count($this->checkLaterNumInGame($laterNumbers, $jogo));

                # Dezenas das 5 que mais são sorteadas
                $checkFrequencyInGame = count($this->checkFrequencyInGame($countFrequency, $jogo));

                // Guardar resultado da avaliação como treino         
                $training['sumDezene'][] = $sumDezene;
                $training['qtdPar'][] = $qtdPar;
                $training['qtdImpar'][] = $qtdImpar;
                $training['yes_15_exist'][] = $yes_15_exist;
                $training['not_10_exist'][] = $not_10_exist;
                $training['yes_prim_exist'][] = $yes_prim_exist;
                $training['not_prim_exist'][] = $not_prim_exist;
                $training['prime_20'][] = $prime_20;
                $training['prim_exist'][] = $prim_exist;
                $training['num_sequence'][] = $num_sequence;
                $training['checkLaterNumInGame'][] = $checkLaterNumInGame;
                $training['checkFrequencyInGame'][] = $checkFrequencyInGame;
            }
        }

        // Guardar resultado da avaliação como treino       
        foreach ($training as $name => $arrTrain) {
            $this->training[$name]['min'] = min($arrTrain);
            $this->training[$name]['max'] = max($arrTrain);
            $this->training[$name]['med'] = (int) (array_sum($arrTrain) / count($arrTrain));
        }
    }

    /**
     * Consultar dados do treinamento
     * Execute o método $this->cal->checkMargin() para carregar o treinamento
     *
     * @return array
     */
    public function getMargin(): array
    {
        return $this->training;
    }

    /**
     * Verificar a menor posição na wordlist que um jogo foi sorteado e a maior posição 
     * para determinar uma margem de verificação na wordlist
     *
     * @return array
     */
    public function getMarginWordList(): array
    {
        $result = [];
        $position = [];
        $getWordlist = $this->getWordlist();

        $i = 0;
        foreach ($this->dataset as $key => $hist) {
            $i++;
            $game = implode(' ', $hist);
            $p = array_search($game, $getWordlist);
            $position[] = $p;
            $result['position'][] = "$i | $p | $game";
        }

        $result['min'] = min($position);
        $result['max'] = max($position);
        $result['med'] = round(array_sum($position) / count($position), 0);

        return $result;
    }

    /**
     * Gerar arquivo txt com a correção da posição de cada jogo na wordlist
     *
     * @return void
     */
    public function positionsMargin(): void
    {
        echo Helper::title('Verificar Range da Wordlist');
        echo date("d/m/Y H:i:s") . " - Inicio. \n";
        $getMarginWordList = $this->getMarginWordList();
        echo date("d/m/Y H:i:s") . " - Fim. \n";
        Helper::saveFile('position.txt', implode("\n", $getMarginWordList['position']));
    }
}
