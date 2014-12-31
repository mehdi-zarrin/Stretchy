<?php namespace Tamayo\Stretchy\Index;

use Closure;
use Tamayo\Stretchy\Connection;
use Tamayo\Stretchy\Index\Grammar;
use Tamayo\Stretchy\Index\Processor;
use Tamayo\Stretchy\Builder as BaseBuilder;
use Tamayo\Stretchy\Exceptions\TypeMustBeDefinedException;
use Tamayo\Stretchy\Exceptions\IndexMustBeDefinedException;

class Builder extends BaseBuilder {

	/**
	 * Index Processor.
	 *
	 * @var \Tamayo\Stretchy\Index\Processor
	 */
	protected $processor;

	/**
	 * Index Builder.
	 *
	 * @param \Tamayo\Stretchy\Connection $connection
	 * @param Grammar                     $grammar
	 */
	public function __construct(Connection $connection, Grammar $grammar, Processor $processor)
	{
		parent::__construct($connection, $grammar);

		$this->processor = $processor;
	}

	/**
	 * Create a new index on Elastic.
	 *
	 * @param  string  $index
	 * @param  Closure $callback
	 * @return \Tamayo\Stretchy\Index\Blueprint
	 */
	public function create($index, Closure $callback)
	{
		$blueprint = $this->createBlueprint($index);

		$blueprint->create();

		$callback($blueprint);

		$this->build($blueprint);
	}

	/**
	 * Deletes an index on Elastic.
	 *
	 * @param  string $index
	 * @return \Tamayo\Stretchy\Index\Blueprint
	 */
	public function delete($index)
	{
		$blueprint = $this->createBlueprint($index);

		$blueprint->delete();

		$this->build($blueprint);
	}

	/**
	 * Insert a document in the engine.
	 *
	 * @param  array  $payload
	 * @return mixed
	 */
	public function insert(array $payload)
	{
		if(! $this->indexIsDefined()) {
			throw new IndexMustBeDefinedException("To perform an insert, you must define an index", 1);
		}

		if(! $this->typeIsDefined()) {
			throw new TypeMustBeDefinedException("To perform an insert, you must define a type", 1);
		}

		$compiled = $this->grammar->compileInsert($this, $payload);

		return $this->processor->processInsert($this, $this->connection->insert($compiled));
	}

	/**
	 * Get Settings of indices.
	 *
	 * @param  string|array $index
	 * @return mixed
	 */
	public function getSettings($index = null)
	{

		if ($index == null) {
			$index = $this->index;
		}

		$prefix = $this->connection->getIndexPrefix();

		if (is_array($index)) {
			foreach ($index as $key => $value) {
				$index[$key] = $prefix.$value;
			}
		}
		else {
			$index = $prefix.$index;
		}

		$compiled = $this->grammar->compileGetSettings($index);

		return $this->processor->processGetSettings($this, $this->connection->indexGetSettings($compiled));
	}

	/**
	 * Create a new blueprint for builder.
	 *
	 * @param  string $index
	 * @param  Closure $callback
	 * @return \Tamayo\Stretch\Index\Blueprint
	 */
	protected function createBlueprint($index, Closure $callback = null)
	{
		return new Blueprint($index, $callback);
	}

	/**
	 * Execute the blueprint to build / modify the table.
	 *
	 * @param  \Tamayo\Stretch\Index\Blueprint $blueprint
	 * @return void
	 */
	protected function build(Blueprint $blueprint)
	{
		$blueprint->build($this->connection, $this->grammar);
	}
}