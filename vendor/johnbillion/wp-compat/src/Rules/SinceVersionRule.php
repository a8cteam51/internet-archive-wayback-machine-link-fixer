<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\CallLike>
 */
final class SinceVersionRule implements Rule {
	private static string $functionIdentifier = 'WPCompat.functionNotAvailable';
	private static string $methodIdentifier = 'WPCompat.methodNotAvailable';
	private static string $errorIdentifier = 'WPCompat.error';

	/**
	 * @var array<string, array{since: string}>
	 */
	private array $symbols;

	private string $minVersion;

	private ReflectionProvider $reflectionProvider;

	public function __construct(
		?string $requiresAtLeast,
		?string $pluginFile,
		ReflectionProvider $reflectionProvider
	) {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$contents = file_get_contents( $symbolsFilePath );

		if ( $contents === false ) {
			throw new \RuntimeException( 'Failed to read symbols.json' );
		}

		$minVersion = $requiresAtLeast ?? self::getRequiresAtLeastValue( $pluginFile );

		$this->minVersion = self::normaliseVersion( $minVersion );
		$this->symbols = json_decode( $contents, true )['symbols'];
		$this->reflectionProvider = $reflectionProvider;
	}

	private static function getRequiresAtLeastValue( ?string $pluginFile ): string {
		$files = [];
		$cwd = getcwd();

		if ( is_string( $pluginFile ) ) {
			$files[] = $pluginFile;
		} elseif ( is_string( $cwd ) ) {
			$base = basename( $cwd );
			$files[] = "{$cwd}/{$base}.php";
			$files[] = "{$cwd}/plugin.php";
			$files[] = "{$cwd}/style.css";
		}

		foreach ( $files as $file ) {
			$path = self::realPath( $file );

			if ( ! is_string( $path ) || ! file_exists( $path ) ) {
				continue;
			}

			return self::getRequiresAtLeastHeader( $path );
		}

		throw new \RuntimeException( 'No plugin or theme file found' );
	}

	/**
	 * The logic in this method matches the logic in WordPress core's get_plugin_data() function.
	 */
	private static function getRequiresAtLeastHeader( string $pluginFile ): string {
		$contents = file_get_contents( $pluginFile );

		if ( ! is_string( $contents ) ) {
			throw new \RuntimeException(
				sprintf(
					'Failed to read file %s',
					$pluginFile,
				)
			);
		}

		// Pull only the first 8 KB of the file in.
		$file_data = substr( $contents, 0, 8 * 1024 );

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );

		// Look for the Requires at least: line.
		$matched = preg_match( '/^[ \t\/*#@]*Requires at least:(.*)$/mi', $file_data, $match );

		if ( $matched === 1 && $match[1] !== '' ) {
			return (string) preg_replace( '#[^0-9\.]#', '', $match[1] );
		}

		throw new \RuntimeException(
			sprintf(
				'Could not read "Requires at least" value from file %s',
				$pluginFile,
			)
		);
	}

	/**
	 * @return string|false
	 */
	private static function realPath( string $file ) {
		$path = realpath( $file );

		if ( is_string( $path ) ) {
			return $path;
		}

		return realpath( getcwd() . DIRECTORY_SEPARATOR . $file );
	}

	private static function normaliseVersion( string $minVersion ): string {
		// Convert a major.minor or major.minor.patch string to a major.minor.patch string:
		$parts = explode( '.', $minVersion );

		if ( count( $parts ) === 2 ) {
			$parts[] = '0';
		}

		return implode( '.', $parts );
	}

	public function getNodeType(): string {
		return CallLike::class;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function processNode( Node $node, Scope $scope ): array {
		if ( $node instanceof FuncCall ) {
			return $this->processFuncCall( $node, $scope );
		}

		// @TODO null-safe method calls
		if ( ( $node instanceof MethodCall ) || ( $node instanceof StaticCall ) ) {
			return $this->processMethodCall( $node, $scope );
		}

		return [];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processFuncCall( FuncCall $node, Scope $scope ): array {
		try {
			$name = self::getFunctionName( $node, $scope );
		} catch ( \RuntimeException $e ) {
			return [
				RuleErrorBuilder::message( $e->getMessage() )->identifier( self::$errorIdentifier )->build(),
			];
		}

		if ( ! is_string( $name ) ) {
			return [];
		}

		if ( $scope->isInFunctionExists( $name ) ) {
			return [];
		}

		if ( ! isset( $this->symbols[ $name ] ) ) {
			return [];
		}

		$since = $this->symbols[ $name ]['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return [];
		}

		$message = sprintf(
			'%s() is only available since %s version %s.',
			$name,
			'WordPress',
			$since,
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( self::$functionIdentifier )->build(),
		];
	}

	private function isInMethodExists( CallLike $node, Scope $scope ): bool {
		if ( ! $node instanceof MethodCall ) {
			return false;
		}

		$methodName = self::getMethodName( $node );

		$inMethodExists = $node->getAttribute( MethodExistsVisitor::ATTRIBUTE_NAME, [] );
		foreach ( $inMethodExists as [$objectOrClass, $method] ) {
			if ( $methodName !== $method->value ) {
				continue;
			}

			if (
				$objectOrClass instanceof Node\Expr\Variable
				&& $node->var instanceof Node\Expr\Variable
			) {
				if ( $node->var->name === $objectOrClass->name ) {
					return true;
				}
			}

			$classNames = $scope->getType( $node->var )->getObjectClassNames();
			if (
				$objectOrClass instanceof Node\Scalar\String_
				&& in_array( $objectOrClass->value, $classNames, true )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param MethodCall|StaticCall $node
	 * @return list<IdentifierRuleError>
	 */
	private function processMethodCall( CallLike $node, Scope $scope ): array {
		if ( $node instanceof MethodCall ) {
			$methodCalledOnType = $scope->getType( $node->var );
			$classNames = $methodCalledOnType->getObjectClassNames();
		} elseif ( $node->class instanceof Name ) {
			$classNames = [
				$node->class->toString(),
			];
		} else {
			return [];
		}

		if ( $this->isInMethodExists( $node, $scope ) ) {
			return [];
		}

		$allClassNames = $classNames;

		// determine the names of all the classes that this class extends from:
		foreach ( $classNames as $className ) {
			try {
				$classReflection = $this->reflectionProvider->getClass( $className );
			} catch ( ClassNotFoundException $e ) {
				// ?
				continue;
			}

			$allClassNames = array_merge( $allClassNames, $classReflection->getParentClassesNames() );
		}

		$methodName = self::getMethodName( $node );
		foreach ( $allClassNames as $className ) {
			try {
				$methodName = self::getMethodName( $node );
			} catch ( \RuntimeException $e ) {
				return [
					RuleErrorBuilder::message( $e->getMessage() )->identifier( self::$errorIdentifier )->build(),
				];
			}

			if ( ! is_string( $methodName ) ) {
				continue;
			}

			$name = sprintf(
				'%s::%s',
				$className,
				$methodName,
			);

			if ( isset( $this->symbols[ $name ] ) ) {
				return $this->processMethodVersion( $name, $this->symbols[ $name ] );
			}
		}

		return [];
	}

	/**
	 * @param array{since: string} $symbol
	 * @return list<IdentifierRuleError>
	 */
	private function processMethodVersion( string $name, array $symbol ): array {
		$since = $symbol['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return [];
		}

		$message = sprintf(
			'%s() is only available since %s version %s.',
			$name,
			'WordPress',
			$since,
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( self::$methodIdentifier )->build(),
		];
	}

	/**
	 * @throws \RuntimeException
	 */
	private static function getFunctionName( FuncCall $node, Scope $scope ): ?string {
		if ( $node->name instanceof Name ) {
			return $node->name->toString();
		}

		if ( $node->name instanceof Variable ) {
			return null;
		}

		throw new \RuntimeException(
			self::error(
				'Failed to get function name from %s in %s:%d. Please report this to https://github.com/johnbillion/wp-compat/issues.',
				$node,
				$scope,
			)
		);
	}

	/**
	 * @param MethodCall|StaticCall $node
	 * @throws \RuntimeException
	 */
	private static function getMethodName( CallLike $node ): ?string {
		if ( $node->name instanceof Identifier ) {
			return $node->name->toString();
		}

		return null;
	}

	/**
	 * @param FuncCall|MethodCall|StaticCall $node
	 */
	private static function error( string $message, CallLike $node, Scope $scope ): string {
		$filename = $scope->getFile();
		$filename = str_replace( getcwd() . '/', '', $filename );

		return sprintf(
			$message,
			get_class( $node->name ),
			$filename,
			$node->getStartLine(),
		);
	}

	public function getMinVersion(): string {
		return $this->minVersion;
	}
}
