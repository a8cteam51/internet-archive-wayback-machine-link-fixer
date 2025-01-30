<?php declare(strict_types = 1);

namespace Swissspidy\PHPStan\Rules\NoPrivate;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use function sprintf;

/**
 * @implements Rule<FuncCall>
 */
class CallToPrivateFunctionRule implements Rule
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
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Name)) {
			return [];
		}

		try {
			$function = $this->reflectionProvider->getFunction($node->name, $scope);
		} catch (FunctionNotFoundException $e) {
			// Other rules will notify if the function is not found
			return [];
		}

		$docComment = $function->getDocComment();

		if ($docComment === null) {
			return [];
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$function->getFileName(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$function->getName(),
			$docComment
		);

		if (!PrivateAnnotationHelper::isPrivate($resolvedPhpDoc)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Call to private/internal function %s().', $function->getName())
			)
	            ->identifier('no.private.function')
	            ->build()
		];
	}

}
