<?php declare(strict_types = 1);

namespace Swissspidy\PHPStan\Rules\NoPrivate;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @implements Rule<StaticCall>
 */
class CallToPrivateStaticMethodRule implements Rule
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var RuleLevelHelper */
	private $ruleLevelHelper;

	/** @var FileTypeMapper */
	private $fileTypeMapper;

	public function __construct(ReflectionProvider $reflectionProvider, RuleLevelHelper $ruleLevelHelper, FileTypeMapper $fileTypeMapper)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->fileTypeMapper = $fileTypeMapper;
	}

	public function getNodeType(): string
	{
		return StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Identifier) {
			return [];
		}

		$methodName = $node->name->name;
		$referencedClasses = [];

		if ($node->class instanceof Name) {
			$referencedClasses[] = $scope->resolveName($node->class);
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				'', // We don't care about the error message
				static function (Type $type) use ($methodName): bool {
					return $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes();
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
				$methodReflection = $class->getMethod($methodName, $scope);
			} catch (ClassNotFoundException $e) {
				continue;
			} catch (MissingMethodFromReflectionException $e) {
				continue;
			}

			if ($methodReflection->isPrivate()) {
				continue;
			}

			$resolvedPhpDoc = $class->getResolvedPhpDoc();
			if ($resolvedPhpDoc !== null && PrivateAnnotationHelper::isPrivate($resolvedPhpDoc)) {
				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Call to method %s() of private/internal class %s.',
						$methodReflection->getName(),
						$methodReflection->getDeclaringClass()->getName()
					)
				)
	                ->identifier('no.private.method')
	                ->build();
				continue;
			}

			$docComment = $methodReflection->getDocComment();

			if ($docComment === null) {
				continue;
			}

			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$methodReflection->getDeclaringClass()->getFileName(),
				$methodReflection->getDeclaringClass()->getName(),
				null,
				$methodReflection->getName(),
				$docComment
			);

			if (!PrivateAnnotationHelper::isPrivate($resolvedPhpDoc)) {
				continue;
			}

			if (
				$scope->isInClass() &&
				$class->getName() === $methodReflection->getDeclaringClass()->getName() &&
				$class->getName() === $scope->getClassReflection()->getName()
			) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'Call to private/internal method %s() of class %s.',
					$methodReflection->getName(),
					$methodReflection->getDeclaringClass()->getName()
				)
			)
                ->identifier('no.private.method')
                ->build();
		}

		return $errors;
	}

}
