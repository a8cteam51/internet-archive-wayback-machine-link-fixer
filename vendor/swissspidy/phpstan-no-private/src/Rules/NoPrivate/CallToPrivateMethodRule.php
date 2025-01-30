<?php declare(strict_types = 1);

namespace Swissspidy\PHPStan\Rules\NoPrivate;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use function sprintf;

/**
 * @implements Rule<MethodCall>
 */
class CallToPrivateMethodRule implements Rule
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var FileTypeMapper */
	private $fileTypeMapper;

	public function __construct(ReflectionProvider $reflectionProvider, FileTypeMapper $fileTypeMapper)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->fileTypeMapper = $fileTypeMapper;
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Identifier) {
			return [];
		}

		$methodName = $node->name->name;
		$methodCalledOnType = $scope->getType($node->var);
		$referencedClasses = $methodCalledOnType->getObjectClassNames();

		foreach ($referencedClasses as $referencedClass) {
			try {
				$classReflection = $this->reflectionProvider->getClass($referencedClass);
				$methodReflection = $classReflection->getMethod($methodName, $scope);

				if ($methodReflection->isPrivate()) {
					continue;
				}

				$docComment = $methodReflection->getDocComment();

				if ($docComment === null) {
					continue;
				}

				$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$methodReflection->getDeclaringClass()->getFileName(),
					$classReflection->getName(),
					null,
					$methodReflection->getName(),
					$docComment
				);

				if (!PrivateAnnotationHelper::isPrivate($resolvedPhpDoc)) {
					continue;
				}


				if (
					$scope->isInClass() &&
					$classReflection->getName() === $methodReflection->getDeclaringClass()->getName()
				) {
					continue;
				}

				return [
					RuleErrorBuilder::message(
						sprintf(
							'Call to private/internal method %s() of class %s.',
							$methodReflection->getName(),
							$methodReflection->getDeclaringClass()->getName()
						)
					)
		                ->identifier('no.private.method')
		                ->build()
				];
			} catch (ClassNotFoundException $e) {
				// Other rules will notify if the class is not found
			} catch (MissingMethodFromReflectionException $e) {
				// Other rules will notify if the the method is not found
			}
		}

		return [];
	}

}
