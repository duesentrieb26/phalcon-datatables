<?php

namespace DataTables;

use DataTables\Adapters\QueryBuilder;
use DataTables\Adapters\ResultSet;
use DataTables\Adapters\ArrayAdapter;
use Phalcon\Di\Injectable;
use Phalcon\Http\Response;

/**
 * Class DataTable
 *
 * @package DataTables
 */
class DataTable extends Injectable
{
  /**
   * @var array
   */
  protected $options;
  /**
   * @var
   */
  protected $params;
  /**
   * @var
   */
  protected $response;
  /**
   * @var ParamsParser
   */
  public $parser;

  /**
   * DataTable constructor.
   *
   * @param array $options
   */
  public function __construct($options = [])
  {
    $default = [
      'limit'  => 20,
      'length' => 50,
    ];

    $this->options = $options + $default;
    $this->parser = new ParamsParser($this->options['limit']);
  }

  /**
   * @return array
   */
  public function getParams()
  {
    return $this->parser->getParams();
  }

  /**
   * @return array
   */
  public function getResponse()
  {
    return !empty($this->response) ? $this->response : [];
  }

  /**
   *
   */
  public function sendResponse()
  {
    if ($this->di->has('view')) {
      $this->di->get('view')->disable();
    }

    $response = new Response();
    $response->setContentType('application/json', 'utf8');
    $response->setJsonContent($this->getResponse());
    $response->send();
    exit;
  }

  /**
   * @param       $builder
   * @param array $columns
   *
   * @return $this
   */
  public function fromBuilder($builder, $columns = [])
  {
    if (empty($columns)) {
      $columns = $builder->getColumns();
      $columns = (is_array($columns)) ? $columns : array_map('trim', explode(',', $columns));
    }

    $adapter = new QueryBuilder($this->options['length']);
    $adapter->setBuilder($builder);
    $adapter->setParser($this->parser);
    $adapter->setColumns($columns);
    $this->response = $adapter->getResponse();

    return $this;
  }

  /**
   * @param       $resultSet
   * @param array $columns
   *
   * @return $this
   */
  public function fromResultSet($resultSet, $columns = [])
  {
    if (empty($columns)) {
      $columns = array_keys($resultSet->getFirst()->toArray());
      $resultSet->rewind();
    }

    $adapter = new ResultSet($this->options['length']);
    $adapter->setResultSet($resultSet);
    $adapter->setParser($this->parser);
    $adapter->setColumns($columns);
    $this->response = $adapter->getResponse();

    return $this;
  }

  /**
   * @param       $array
   * @param array $columns
   *
   * @return $this
   */
  public function fromArray($array, $columns = [])
  {
    if (empty($columns)) {
      $columns = array_keys(current($array));
    }

    $adapter = new ArrayAdapter($this->options['length']);
    $adapter->setArray($array);
    $adapter->setParser($this->parser);
    $adapter->setColumns($columns);
    $this->response = $adapter->getResponse();

    return $this;
  }
}
