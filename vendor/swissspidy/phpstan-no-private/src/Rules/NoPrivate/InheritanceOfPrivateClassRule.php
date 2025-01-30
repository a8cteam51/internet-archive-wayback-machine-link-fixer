<?php declare(strict_types = 1);

namespace Swissspidy\PHPStan\Rules\NoPrivate;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Class_>
 */
class InheritanceOfPrivateClassRule implements Rule
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->extends === null) {
			return [];
		}

		$errors = [];

		$className = isset($node->namespacedName)
			? (string) $node->namespacedName
			: (string) $node->name;

		try {
			$class = $this->reflectionProvider->getClass($className);
		} catch (ClassNotFoundException $e) {
			return [];
		}

		$parentClassName = (string) $node->extends;

		try {
			$parentClass = $this->reflectionProvider->getClass($parentClassName);
			$resolvedPhpDoc = $parentClass->getResolvedPhpDoc();
			if ($resolvedPhpDoc === null || !PrivateAnnotationHelper::isPrivate($resolvedPhpDoc)) {
				return $errors;
			}

			if (!$class->isAnonymous()) {
				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Class %s extends private/internal class %s.',
						$className,
						$parentClassName
					)
				)
                    ->identifier('no.private.class')
                    ->build();
			} else {
				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Anonymous class extends private/internal class %s.',
						$parentClassName
					)
				)
                    ->identifier('no.private.class')
                    ->build();
			}
		} catch (ClassNotFoundException $e) {
			// Other rules will notify if the interface is not found
		}

		return $errors;
	}

}
