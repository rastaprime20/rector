<?php

declare(strict_types=1);

namespace Rector\_Package_\Rector\_Category_;

use PhpParser\Node;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
_Source_
 * @see \Rector\_Package_\Tests\Rector\_Category_\_Name_\_Name_Test
 */
final class _Name_ extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('_Description_', [
            new CodeSample(
                _CodeBeforeExample_,
                _CodeAfterExample_
            )
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return _NodeTypes_Php_;
    }

    /**
     * @param _NodeTypes_Doc_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // change the node

        return $node;
    }
}
