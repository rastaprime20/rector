<?php

declare(strict_types=1);

namespace Rector\SOLID\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\Core\PhpParser\Node\Manipulator\VariableManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\Core\Util\RectorStrings;
use Rector\NodeTypeResolver\Node\AttributeKey;

/**
 * @see \Rector\SOLID\Tests\Rector\ClassMethod\ChangeReadOnlyVariableWithDefaultValueToConstantRector\ChangeReadOnlyVariableWithDefaultValueToConstantRectorTest
 */
final class ChangeReadOnlyVariableWithDefaultValueToConstantRector extends AbstractRector
{
    /**
     * @var VariableManipulator
     */
    private $variableManipulator;

    public function __construct(VariableManipulator $variableManipulator)
    {
        $this->variableManipulator = $variableManipulator;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Change variable with read only status with default value to constant', [
            new CodeSample(
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        $replacements = [
            'PHPUnit\Framework\TestCase\Notice' => 'expectNotice',
            'PHPUnit\Framework\TestCase\Deprecated' => 'expectDeprecation',
        ];

        foreach ($replacements as $class => $method) {
        }
    }
}
PHP
,
                <<<'PHP'
class SomeClass
{
    private const REPLACEMENTS = [
        'PHPUnit\Framework\TestCase\Notice' => 'expectNotice',
        'PHPUnit\Framework\TestCase\Deprecated' => 'expectDeprecation',
    ];

    public function run()
    {
        foreach (self::REPLACEMENTS as $class => $method) {
        }
    }
}
PHP

            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $assignsOfArrayToVariable = $this->variableManipulator->collectAssignsOfVariable($node);

        $readOnlyVariableAssigns = $this->variableManipulator->filterOutReadOnlyVariables(
            $assignsOfArrayToVariable,
            $node
        );

        foreach ($readOnlyVariableAssigns as $readOnlyVariableAssign) {
            $this->removeNode($readOnlyVariableAssign);

            /** @var Variable $variable */
            $variable = $readOnlyVariableAssign->var;
            $classConst = $this->createClassConst($variable, $readOnlyVariableAssign->expr);

            /** @var Class_ $class */
            $class = $node->getAttribute(AttributeKey::CLASS_NODE);

            // replace $variable usage in the code with constant
            $this->addConstantToClass($class, $classConst);

            $this->replaceVariableWithClassConstFetch($node, $classConst);
        }

        return $node;
    }

    private function createClassConst(Variable $variable, Expr $expr): ClassConst
    {
        $constantName = $this->createConstantNameFromVariable($variable);

        $constant = new Const_($constantName, $expr);

        $classConst = new ClassConst([$constant]);
        $classConst->flags = Class_::MODIFIER_PRIVATE;

        // @todo decouple to comment mirror service
        $classConst->setAttribute(AttributeKey::PHP_DOC_INFO, $variable->getAttribute(AttributeKey::PHP_DOC_INFO));
        $classConst->setAttribute('comments', $variable->getAttribute('comments'));

        return $classConst;
    }

    private function createConstantNameFromVariable(Variable $variable): string
    {
        $variableName = $this->getName($variable);
        if ($variableName === null) {
            throw new ShouldNotHappenException();
        }

        $constantName = RectorStrings::camelCaseToUnderscore($variableName);

        return strtoupper($constantName);
    }

    private function replaceVariableWithClassConstFetch(ClassMethod $classMethod, ClassConst $classConst): void
    {
        $constantName = $this->getName($classConst);
        if ($constantName === null) {
            throw new ShouldNotHappenException();
        }

        $this->traverseNodesWithCallable($classMethod, function (Node $node) use ($constantName) {
            if (! $node instanceof Variable) {
                return null;
            }

            if (! $this->isName($node, $constantName)) {
                return null;
            }

            // replace with constant fetch
            return new ClassConstFetch(new Name('self'), new Identifier($constantName));
        });
    }
}
