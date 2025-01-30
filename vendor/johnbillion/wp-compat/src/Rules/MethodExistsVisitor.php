<?php declare(strict_types = 1);

namespace WPCompat\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

final class MethodExistsVisitor extends NodeVisitorAbstract {


	public const ATTRIBUTE_NAME = 'in_method_exists';

	/**
	 * @var array<int, list<array{Node\Expr, Node\Scalar\String_}>>
	 */
	private array $inMethodExists = [];

	/**
	 * @var list<Node\Stmt\If_>
	 */
	private array $ifStack = [];

	public function enterNode( Node $node ): ?Node {
		if ( $node instanceof Node\Stmt\If_ ) {
			$this->ifStack[] = $node;
		}

		if ( $node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && ! $node->isFirstClassCallable() ) {
			$functionName = $node->name->toLowerString();
			if ( $functionName === 'method_exists' ) {
				$args = $node->getArgs();
				if (
					count( $args ) >= 2
					&& $args[1]->value instanceof Node\Scalar\String_
				) {
					if ( ! array_key_exists( count( $this->ifStack ), $this->inMethodExists ) ) {
						$this->inMethodExists[ count( $this->ifStack ) ] = [];
					}
					$this->inMethodExists[ count( $this->ifStack ) ][] = [ $args[0]->value, $args[1]->value ];
				}
			}
		} elseif ( $node instanceof Node\Expr\CallLike && count( $this->inMethodExists ) > 0 ) {
			$node->setAttribute( self::ATTRIBUTE_NAME, $this->inMethodExists[ count( $this->ifStack ) ] );
		}

		return null;
	}

	public function leaveNode( Node $node ) {
		if ( $node instanceof Node\Stmt\If_ ) {
			unset( $this->inMethodExists[ count( $this->ifStack ) ] );
			array_pop( $this->ifStack );
		}

		return null;
	}
}
