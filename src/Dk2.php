<?php
/**
 * This file is part of the Dk20161002.Dk
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Dk20161002\Dk;

use Quartet\Haydn\IO\Source\SingleColumnArraySource;
use Quartet\Haydn\Matcher\Matcher;
use Quartet\Haydn\Set;

class Dk2
{
    private $areas = [];

    public function main($input)
    {
        // 入力をパース
        list($whites, $blacks) = $this->parseInput($input);

        // 白黒それぞれで組み合わせ
        $whiteStoneFactory = function ($key) {
            return function($row) use ($key) {
                return [$key => new Stone($row[$key], 'w')];
            };
        };
        $blackStoneFactory = function ($key) {
            return function($row) use ($key) {
                return [$key => new Stone($row[$key], 'b')];
            };
        };
        $p = new Set(new SingleColumnArraySource('p', $whites));
        $p = $p->select([$whiteStoneFactory('p')]);
        $q = new Set(new SingleColumnArraySource('q', $whites));
        $q = $q->select([$whiteStoneFactory('q')]);
        $whitePairs = $p->product($q);
        $p = new Set(new SingleColumnArraySource('p', $blacks));
        $p = $p->select([$blackStoneFactory('p')]);
        $q = new Set(new SingleColumnArraySource('q', $blacks));
        $q = $q->select([$blackStoneFactory('q')]);
        $blackPairs = $p->product($q);

        // 長方形を構成するのものみにフィルタ
        $matcher = new Matcher(['p' => function ($val, $row) use ($whites, $blacks) {
            if (!$row['p']->canRect($row['q'])) return false;

            // 頂点（他の2点）に同色の石があるか？
            $p1Pos = $row['p']->getColChar() . $row['q']->getRowChar();
            $q1Pos = $row['q']->getColChar() . $row['p']->getRowChar();

            $ours = ($row['p']->getColor() == 'w') ? $whites : $blacks;
            $theirs = ($row['p']->getColor() == 'w') ? $blacks : $whites;

            if (array_search($p1Pos, $ours) === false || array_search($q1Pos, $ours) === false) {
                return false;
            }

            // 左上から右下までに敵があればNG
            $positions = $this->createPositions($row['p'], $row['q']);
            foreach ($positions as $position) {
                if (array_search($position, $theirs) !== false) {
                    return false;
                }
            }

            return true;
        }]);
        $whitePairs = $whitePairs->filter($matcher);
        $blackPairs = $blackPairs->filter($matcher);

        foreach ($whitePairs->union($blackPairs) as $stonePair) {
            $this->addArea($stonePair);
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

    private function addArea($stonePair)
    {
        /** @var Stone $p */
        $p = $stonePair['p'];
        /** @var Stone $q */
        $q = $stonePair['q'];

        foreach ($this->createAreaLeftTops($p, $q) as $leftTopPos) {
            $this->areas[$leftTopPos] = $p->getColor();
        }
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

    private function createPositions(Stone $p, Stone $q)
    {
        $col1 = $p->getCol();
        $col2 = $q->getCol();
        $row1 = $p->getRow();
        $row2 = $q->getRow();

        for ($col = min($col1, $col2), $cmax = max($col1, $col2); $col <= $cmax; $col++) {
            for ($row = min($row1, $row2), $rmax = max($row1, $row2); $row <= $rmax; $row++) {
                yield chr($col + 97) . ($row + 1);
            }
        }
    }

    private function createAreaLeftTops(Stone $p, Stone $q)
    {
        $col1 = $p->getCol();
        $col2 = $q->getCol();
        $row1 = $p->getRow();
        $row2 = $q->getRow();

        for ($col = min($col1, $col2), $cmax = max($col1, $col2); $col <= $cmax - 1; $col++) {
            for ($row = min($row1, $row2), $rmax = max($row1, $row2); $row <= $rmax - 1; $row++) {
                yield chr($col + 97) . ($row + 1);
            }
        }
    }
}

class Stone
{
    private $pos;
    private $col;
    private $row;
    private $color;

    public function __construct($pos, $color)
    {
        $this->pos = $pos;
        preg_match('/([a-z])([0-9]+)/', $pos, $match);
        $this->col = ord($match[1]) - 97;
        $this->row = (int)$match[2] - 1;
        $this->color = $color;
    }

    /**
     * @return mixed
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * @return int
     */
    public function getCol()
    {
        return $this->col;
    }

    public function getColChar()
    {
        return chr($this->col + 97);
    }

    /**
     * @return int
     */
    public function getRow()
    {
        return $this->row;
    }

    public function getRowChar()
    {
        return $this->row + 1;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * 矩形を構成可能か？
     * @param Stone $target
     * @return bool
     */
    public function canRect(Stone $target)
    {
        return $this->col != $target->col && $this->row != $target->row;
    }
}
