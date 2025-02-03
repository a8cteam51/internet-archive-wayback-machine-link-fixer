<?php declare(strict_types = 1);

namespace Swissspidy\PHPStan\Rules\NoPrivate;

use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\PhpDocBlock;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use function array_column;
use function var_dump;

class PrivateAnnotationHelper
{

	public static function isPrivate( ResolvedPhpDocBlock $phpDocBlock ) : bool
	{
		foreach($phpDocBlock->getPhpDocNodes() as $phpDocNode) {
			foreach(array_column($phpDocNode->getTagsByName('@access'), 'value') as $accessTagValue) {
				$scope = (string) $accessTagValue;
				if ( 'private' === $scope) {
					return true;
				}
			}
		}

		return false;
	}

}
