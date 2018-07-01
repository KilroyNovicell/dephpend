<?php

declare(strict_types=1);

namespace Mihaeu\PhpDependencies\Util;

/**
 * @covers Mihaeu\PhpDependencies\Util\Util
 */
class UtilTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayMatchesAtLeastOnce()
    {
        assertTrue(Util::array_once([1, 2, 3, 'tt'], function ($value, $index) {
            return $value === 'tt';
        }));
    }

    public function testArrayMatchesIndex()
    {
        assertTrue(Util::array_once([1, 2, 3, 'tt'], function ($value, $index) {
            return $index === 3 && $value === 'tt';
        }));
    }

    public function testArrayMatchesNothing()
    {
        assertFalse(Util::array_once([1, 2, 3, 'tt'], function ($value, $index) {
            return $value === 'xxx';
        }));
    }

    public function testReduceArrayWithKeys()
    {
        assertEquals('0a1b2c3d', Util::reduce(['a', 'b', 'c', 'd'], function (string $carry, int $index, string $value) {
            return $carry.$index.$value;
        }, ''));
    }
}
