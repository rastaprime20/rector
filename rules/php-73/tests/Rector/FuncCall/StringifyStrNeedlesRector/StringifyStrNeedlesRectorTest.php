<?php

declare(strict_types=1);

namespace Rector\Php73\Tests\Rector\FuncCall\StringifyStrNeedlesRector;

use Iterator;
use Rector\Core\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;

final class StringifyStrNeedlesRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return StringifyStrNeedlesRector::class;
    }
}
