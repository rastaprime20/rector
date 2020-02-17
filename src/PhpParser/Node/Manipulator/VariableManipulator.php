<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\Node\Manipulator;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\PhpParser\NodeTraverser\CallableNodeTraverser;
use Rector\Core\PhpParser\Printer\BetterStandardPrinter;
use Rector\NodeTypeResolver\Node\AttributeKey;

final class VariableManipulator
{
    /**
     * @var CallableNodeTraverser
     */
    private $callableNodeTraverser;

    /**
     * @var AssignManipulator
     */
    private $assignManipulator;

    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;

    public function __construct(
        CallableNodeTraverser $callableNodeTraverser,
        AssignManipulator $assignManipulator,
        BetterStandardPrinter $betterStandardPrinter,
        BetterNodeFinder $betterNodeFinder
    ) {
        $this->callableNodeTraverser = $callableNodeTraverser;
        $this->assignManipulator = $assignManipulator;
        $this->betterStandardPrinter = $betterStandardPrinter;
        $this->betterNodeFinder = $betterNodeFinder;
    }

    /**
     * @return Assign[]
     */
    public function collectAssignsOfVariable(ClassMethod $classMethod): array
    {
        $assignsOfArrayToVariable = [];

        $this->callableNodeTraverser->traverseNodesWithCallable((array) $classMethod->getStmts(), function (Node $node) use (
            &$assignsOfArrayToVariable
        ) {
            if (! $node instanceof Assign) {
                return null;
            }

            if (! $node->var instanceof Variable) {
                return null;
            }

            if (! $node->expr instanceof Array_ && ! $node->expr instanceof Scalar) {
                return null;
            }

            $assignsOfArrayToVariable[] = $node;
        });

        return $assignsOfArrayToVariable;
    }

    /**
     * @param Assign[] $assignsOfArrayToVariable
     * @return Assign[]
     */
    public function filterOutReadOnlyVariables(array $assignsOfArrayToVariable, ClassMethod $classMethod): array
    {
        $readOnlyVariableAssigns = [];

        foreach ($assignsOfArrayToVariable as $assignOfArrayToVariable) {
            /** @var Variable $variable */
            $variable = $assignOfArrayToVariable->var;
            if (! $this->isReadOnlyVariable($classMethod, $variable, $assignOfArrayToVariable)) {
                continue;
            }

            $readOnlyVariableAssigns[] = $assignOfArrayToVariable;
        }

        return $readOnlyVariableAssigns;
    }

    /**
     * Inspiration
     * @see \Rector\Core\PhpParser\Node\Manipulator\PropertyManipulator::isReadOnlyProperty()
     */
    private function isReadOnlyVariable(ClassMethod $classMethod, Variable $variable, Assign $assign): bool
    {
        $variableUsages = $this->betterNodeFinder->find((array) $classMethod->getStmts(), function (Node $node) use (
            $variable,
            $assign
        ) {
            if (! $node instanceof Variable) {
                return false;
            }

            // skip initialization
            $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
            if ($parentNode === $assign) {
                return false;
            }

            return $this->betterStandardPrinter->areNodesWithoutCommentsEqual($node, $variable);
        });

        foreach ($variableUsages as $variableUsage) {
            if (! $this->assignManipulator->isNodeLeftPartOfAssign($variableUsage)) {
                continue;
            }

            return false;
        }

        return true;
    }
}
