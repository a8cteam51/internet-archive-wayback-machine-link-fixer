<?php declare(strict_types = 1);

namespace Swissspidy\PHPStan\Rules\NoPrivate;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use function sprintf;

/**
 * @implements Rule<New_>
 */
class InstantiationOfPrivateClassRule implements Rule
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var RuleLevelHelper */
	private $ruleLevelHelper;

	public function __construct(ReflectionProvider $reflectionProvider, RuleLevelHelper $ruleLevelHelper)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$referencedClasses = [];

		if ($node->class instanceof Name) {
			$referencedClasses[] = $scope->resolveName($node->class);
		} elseif ($node->class instanceof Class_) {
			if (!isset($node->class->namespacedName)) {
				return [];
			}

			$referencedClasses[] = $scope->resolveName($node->class->namespacedName);
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				'', // We don't care about the error message
				static function (): bool {
					return true;
				}
			);

			if ($classTypeResult->getType() instanceof ErrorType) {
				return [];
			}

			$referencedClasses = $classTypeResult->getReferencedClasses();
		}

		$errors = [];

		foreach ($referencedClasses as $referencedClass) {
			try {
				$class = $this->reflectionProvider->getClass($referencedClass);
			} catch (ClassNotFoundException $e) {
				continue;
			}

			$resolvedPhpDoc = $class->getResolvedPhpDoc();
			if ($resolvedPhpDoc === null || !PrivateAnnotationHelper::isPrivate($resolvedPhpDoc)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'Instantiation of private/internal class %s.',
					$referencedClass
				)
			)
                ->identifier('no.private.class')
                ->build();
		}

		return $errors;
	}

}
