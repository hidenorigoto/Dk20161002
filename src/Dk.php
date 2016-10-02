<?php
/**
 * This file is part of the Dk20161002.Dk
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Dk20161002\Dk;

class Dk
{
    private $columns = [];
    private $stones = [];
    private $areas = [];

    public function main($input)
    {
        // 入力をパース
        list($whites, $blacks) = $this->parseInput($input);

        // 入力を配置
        $this->columns = array_pad([], 19, array_pad([], 19, null));
        $this->stones = [];

        foreach ($whites as $place) {
            preg_match('/([a-z])([0-9]+)/', $place, $match);
            $col = ord($match[1]) - 97;
            $row = (int)$match[2] - 1;

            $this->columns[$col][$row] = 'w';
            $this->stones[] = ['col' => $col, 'row' => $row, 'color' => 'w'];
        }
        foreach ($blacks as $place) {
            preg_match('/([a-z])([0-9]+)/', $place, $match);
            $col = ord($match[1]) - 97;
            $row = (int)$match[2] - 1;

            $this->columns[$col][$row] = 'b';
            $this->stones[] = ['col' => $col, 'row' => $row, 'color' => 'b'];
        }

        $this->sort();

        // 左上から順に
        foreach ($this->stones as $stone) {
            echo PHP_EOL . '------------------------' . PHP_EOL .'checking stone ' . $this->stonePosChar($stone) . PHP_EOL;

            // すべての行内候補を試す
            $rowCurrent = 0;
            while (($rowNextStone = $this->getRowNext($stone, $rowCurrent)) != null) {

                echo PHP_EOL . ' found stone in same row:' . $this->stonePosChar($rowNextStone) . PHP_EOL;
                $rowCurrent = $rowNextStone['col'];

                // すべての列内候補を試す
                $colCurrent = 0;
                while (($colNextStone = $this->getColNext($stone, $colCurrent)) != null) {

                    echo PHP_EOL . ' found stone in same col:' . $this->stonePosChar($colNextStone) . PHP_EOL;

                    $colCurrent = $colNextStone['row'];

                    // 右下に同色の石があるか？
                    if ($this->columns[$rowNextStone['col']][$colNextStone['row']] != $stone['color']) {
                        echo "右下なし";
                        continue;
                    }

                    // 左上から右下まで、別色の石がないか？
                    if ($this->hasEnemy($stone, $colNextStone, $rowNextStone)) {
                        echo "途中に別の色あり";
                        continue;
                    }

                    echo PHP_EOL . '!ADD ' . PHP_EOL;

                    $this->addArea($stone, $rowNextStone, $colNextStone);
                }

            }


        }

        return $this->getResult();
    }



    /**
     * @param $input
     * @return array
     */
    public function parseInput($input)
    {
        list($whites, $blacks) = explode(',', $input);

        preg_match_all('/([a-z][0-9]+)/', $whites, $match);
        $whites = $match[1];

        preg_match_all('/([a-z][0-9]+)/', $blacks, $match);
        $blacks = $match[1];

        return [$whites, $blacks];
    }

    private function sort()
    {
        foreach ($this->stones as $index=>$data) {
            ksort($this->stones[$index]);
        }
        ksort($this->stones);
    }

    private function getRowNext($stone, $current)
    {
        echo PHP_EOL;
        echo 'current:' . $this->stonePosChar($stone) . ' ↓ ';
        for ($i = max($stone['col'] + 1, $current + 1); $i <= 18; $i++) {
            $target = $this->columns[$i][$stone['row']];
            echo $target;
            if ($target == null) {
                continue;
            } elseif ($target != $stone['color']) {
                return null;
            } elseif ($target == $stone['color']) {
                return ['row' => $stone['row'], 'col' => $i, 'color' => $target];
            }
        }

        return null;
    }

    private function getColNext($stone, $current)
    {
        echo PHP_EOL;
        echo 'current:' . $this->stonePosChar($stone) . ' → ';
        for ($i = max($stone['row'] + 1, $current + 1); $i <= 18; $i++) {
            $target = $this->columns[$stone['col']][$i];
            echo $target;
            if ($target == null) {
                continue;
            } elseif ($target != $stone['color']) {
                return null;
            } elseif ($target == $stone['color']) {
                return ['row' => $i, 'col' => $stone['col'], 'color' => $target];
            }
        }

        return null;
    }

    private function addArea($stone, $rowNextStone, $colNextStone)
    {
        for ($i = $stone['col']; $i <= $rowNextStone['col'] - 1; $i++) {
            for ($j = $stone['row']; $j <= $colNextStone['row'] - 1; $j++) {
                $this->areas[sprintf('%s%d', chr(97 +$i), $j + 1)] = $stone['color'];
            }
        }
    }

    private function stonePosChar($stone) {
        return sprintf('%s%d(%s)', chr(97 + $stone['col']), $stone['row'] + 1, $stone['color']);
    }

    private function hasEnemy($stone, $colNextStone, $rowNextStone) {
        for ($i = $stone['row']; $i <= $colNextStone['row']; $i++) {
            for ($j = $stone['col']; $j <= $rowNextStone['col']; $j++) {
                if (($this->columns[$j][$i] != null) && ($this->columns[$j][$i] != $stone['color'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getResult()
    {
        $w = count(array_filter($this->areas, function($val) {
            return $val == 'w';
        }));
        $b = count(array_filter($this->areas, function($val) {
            return $val == 'b';
        }));

        return sprintf('%d,%d', $w, $b);
    }
}
