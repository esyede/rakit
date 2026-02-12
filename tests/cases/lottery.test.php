<?php

defined('DS') or exit('No direct access.');

use System\Lottery;

class LotteryTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        Lottery::normal();
    }

    public function testItCanWin()
    {
        $wins = false;
        Lottery::odds(1, 1)->winner(function () use (&$wins) {
            $wins = true;
        })->choose();

        $this->assertTrue($wins);
    }

    public function testItCanLose()
    {
        $wins = false;
        $loses = false;

        Lottery::odds(0, 1)->winner(function () use (&$wins) {
            $wins = true;
        })->loser(function () use (&$loses) {
            $loses = true;
        })->choose();

        $this->assertFalse($wins);
        $this->assertTrue($loses);
    }

    public function testItCanReturnValues()
    {
        $win = Lottery::odds(1, 1)->winner(function () {
            return 'win';
        })->choose();
        $this->assertSame('win', $win);

        $lose = Lottery::odds(0, 1)->loser(function () {
            return 'lose';
        })->choose();
        $this->assertSame('lose', $lose);
    }

    public function testItCanChooseSeveralTimes()
    {
        $results = Lottery::odds(1, 1)->winner(function () {
            return 'win';
        })->choose(2);
        $this->assertSame(['win', 'win'], $results);

        $results = Lottery::odds(0, 1)->loser(function () {
            return 'lose';
        })->choose(2);
        $this->assertSame(['lose', 'lose'], $results);
    }

    public function testWithoutSpecifiedClosuresBooleansAreReturned()
    {
        $win = Lottery::odds(1, 1)->choose();
        $this->assertTrue($win);

        $lose = Lottery::odds(0, 1)->choose();
        $this->assertFalse($lose);
    }

    public function testItCanForceWinningResultInTests()
    {
        $result = null;
        Lottery::always_win(function () use (&$result) {
            $result = Lottery::odds(1, 2)->winner(function () {
                return 'winner';
            })->choose(10);
        });

        $this->assertSame([
            'winner', 'winner', 'winner', 'winner', 'winner',
            'winner', 'winner', 'winner', 'winner', 'winner',
        ], $result);
    }

    public function testItCanForceLosingResultInTests()
    {
        $result = null;
        Lottery::always_lose(function () use (&$result) {
            $result = Lottery::odds(1, 2)->loser(function () {
                return 'loser';
            })->choose(10);
        });

        $this->assertSame([
            'loser', 'loser', 'loser', 'loser', 'loser',
            'loser', 'loser', 'loser', 'loser', 'loser',
        ], $result);
    }

    public function testItCanForceTheResultViaSequence()
    {
        $result = null;
        Lottery::sequence([
            true, false, true, false, true,
            false, true, false, true, false,
        ]);

        $result = Lottery::odds(1, 100)->winner(function () {
            return 'winner';
        })->loser(function () {
            return 'loser';
        })->choose(10);

        $this->assertSame([
            'winner', 'loser', 'winner', 'loser', 'winner',
            'loser', 'winner', 'loser', 'winner', 'loser',
        ], $result);
    }

    public function testItCanHandleMissingSequenceItems()
    {
        $result = null;
        Lottery::sequence([
            0 => true,
            1 => true,
            // 2 => ...
            3 => true,
        ], function () {
            throw new \RuntimeException('Missing key in sequence.');
        });

        $result = Lottery::odds(1, 10000)->winner(function() {
            return 'winner';
        })->loser(function () {
            return 'loser';
        })->choose();
        $this->assertSame('winner', $result);

        $result = Lottery::odds(1, 10000)->winner(function() {
            return 'winner';
        })->loser(function () {
            return 'loser';
        })->choose();
        $this->assertSame('winner', $result);

        $message = null;
        try {
            Lottery::odds(1, 10000)->winner(function() {
                return 'winner';
            })->loser(function () {
                return 'loser';
            })->choose();
        } catch (\Throwable $e) {
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertSame($message, 'Missing key in sequence.');
    }

    public function testItThrowsForFloatsOverOne()
    {
        $message = null;
        try {
            new Lottery(1.1);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertSame($message, 'Float must not be greater than 1.');
    }

    public function testItCanWinWithFloat()
    {
        $wins = false;

        Lottery::odds(1.0)->winner(function () use (&$wins) {
            $wins = true;
        })->choose();

        $this->assertTrue($wins);
    }

    public function testItCanLoseWithFloat()
    {
        $wins = false;
        $loses = false;

        Lottery::odds(0.0)->winner(function () use (&$wins) {
            $wins = true;
        })->loser(function () use (&$loses) {
            $loses = true;
        })->choose();

        $this->assertFalse($wins);
        $this->assertTrue($loses);
    }
}
