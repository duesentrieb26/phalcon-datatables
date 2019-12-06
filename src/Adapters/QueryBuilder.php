<?php

namespace DataTables\Adapters;

use Phalcon\Paginator\Adapter\QueryBuilder as PQueryBuilder;

class QueryBuilder extends AdapterInterface
{
  /**
   * @var \Phalcon\Mvc\Model\Query\BuilderInterface
   */
  protected $builder;

  /**
   * @var array
   */
  protected $typeMapping
    = [
      "equal"         => [
        "clause" => "%s = %s",
        "value"  => "%s",
      ],
      "like"          => [
        "clause" => "%s LIKE %s",
        "value"  => "%s",
      ],
      "greater"       => [
        "clause" => "%s > %s",
        "value"  => "%s",
      ],
      "greater_equal" => [
        "clause" => "%s >= %s",
        "value"  => "%s",
      ],
      "lower"         => [
        "clause" => "%s < %s",
        "value"  => "%s",
      ],
      "lower_equal"   => [
        "clause" => "%s <= %s",
        "value"  => "%s",
      ],
      "not_equal"     => [
        "clause" => "%s != %s",
        "value"  => "%s",
      ],
      "not_like"      => [
        "clause" => "%s NOT LIKE %s",
        "value"  => "%s",
      ],
      "in"            => [
        "clause" => " %s IN (%s)",
        "value"  => "%s",
      ],
      "not_in"        => [
        "clause" => "%s NOT IN (%s)",
        "value"  => "%s",
      ],
      "between"       => [
        "clause" => "%s BETWEEN %s AND %s",
        "value"  => "%s",
      ],
      "not_between"   => [
        "clause" => "%s NOT BETWEEN %s AND %s",
        "value"  => "%s",
      ],
      "regex"         => [
        "clause" => "%s REGEX %s",
        "value"  => "%s",
      ],
      "not_regex"     => [
        "clause" => "%s NOT REGEX %s",
        "value"  => "%s",
      ],
    ];

  public function setBuilder($builder)
  {
    $this->builder = $builder;
  }

  public function getResponse()
  {
    $builder = new PQueryBuilder(
      [
        'builder' => $this->builder,
        'limit'   => 1,
        'page'    => 1,
      ]
    );

    $total = $builder->paginate();

    $this->bind(
      'global_search', function ($column, $search) {
      $this->builder->orWhere("{$column} LIKE ?0", ["%{$search}%"]);
    }
    );

    $this->bind(
      'column_search', function ($column, $search) {
      $this->builder->andWhere("{$column} LIKE :key_{$column}:", ["key_{$column}" => "%{$search}%"]);
    }
    );

    $this->bind(
      'external_search', function ($column, $type, $search) {

      $type = mb_strtolower($type);
      $search = str_replace('*', '%', $search);
      if (array_key_exists($type, $this->typeMapping)) {
        $clause = sprintf($this->typeMapping[$type]['clause'], $column, ':key_' . $column . ":");
        $search = sprintf($this->typeMapping[$type]['value'], $search);
      } else {
        throw new \Exception('no valid search type ' . $type);
      }
      $this->builder->andWhere($clause, ["key_{$column}" => $search]);
    }
    );

    $this->bind(
      'order', function ($order) {
      $this->builder->orderBy(implode(', ', $order));
    }
    );

    $builder = new PQueryBuilder(
      [
        'builder' => $this->builder,
        'limit'   => $this->parser->getLimit(),
        'page'    => $this->parser->getPage(),
      ]
    );
    /** @var \Phalcon\Paginator\Repository $filtered */
    $filtered = $builder->paginate();

    return $this->formResponse(
      [
        'total'    => $total->getTotalItems(),
        'filtered' => $filtered->getTotalItems(),
        'data'     => $filtered->getItems(),
        'phql'     => $this->builder->getPhql(),
      ]
    );
  }

}
