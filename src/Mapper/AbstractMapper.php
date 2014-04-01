<?php
/**
 * Author: Nickolay U. Kofanov
 * Company: CodeTiburon
 * Last Edited: 15.06.2013
 */
namespace Zf2Db\Mapper;

use \Zend\Db\TableGateway\AbstractTableGateway;
use \Zend\Db\Adapter\Adapter;
use \Zend\Db\Adapter\AdapterAwareInterface;
use \Zend\ServiceManager\ServiceLocatorAwareInterface;
use \Zend\ServiceManager\ServiceLocatorInterface;
use \Zend\Db\ResultSet\ResultSet;
use \Zend\Db\Sql\Where;
use \Zend\Db\Adapter\Platform\Mysql;

abstract class AbstractMapper extends AbstractTableGateway
    implements AdapterAwareInterface, ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var string
     */
    protected $model = 'Zend\Stdlib\ArrayObject';

    /**
     * Set db adapter
     *
     * @param Adapter $adapter
     * @return AdapterAwareInterface
     */
    public function setDbAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;

        if (is_string($this->model)) {
            $class = $this->model;
            $model = new $class();
        }
        else {
            $model = $this->model;
        }

        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, $model);

        return $this;
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Get database connection
     *
     * @return \Zend\Db\Adapter\Driver\ConnectionInterface|null
     */
    public function getConnection()
    {
        return $this->adapter ? $this->adapter->getDriver()->getConnection() : null ;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Is table empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        $result =
            $this->selectWith(
                $this->sql->select()
                    ->columns(array('n' => new SqlExpression('NULL')))
                    ->limit(1)
            )->current();

        return empty($result);
    }

    /**
     * @param       $where
     * @param array $columns
     * @param array $order
     * @param string $group
     *
     * @return null|\Zend\Db\ResultSet\ResultSetInterface
     */
    public function getItems ($where, $columns = array('*'), $order = array(), $group = '')
    {
        $select = $this->getSql()->select();

        if (!empty($where)) {
            $select->where($where);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        if (!empty($group)) {
            $select->group($group);
        }

        $select->columns($columns);

        $query = $select->getSqlString(new Mysql($this->adapter->getDriver()));

        return $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Get row count in the table
     *
     * @param  Where|\Closure|string|array|\Zend\Db\Sql\Predicate\PredicateInterface $where
     * @return mixed
     */
    public function getRowCount($where = null)
    {
        $select = $this->sql->select()->columns(array('n' => new SqlExpression('COUNT(*)')));
        if ($where) {
            $select = $select->where($where);
        }

        $result = $this->selectWith($select)->current();
        return $result['n'];
    }

    /**
     * magic call that allows find rows by field value:
     *
     *   $table->findByName('user');
     *   $table->findById('>', 5);
     *   $table->findByTime('IN', 'SELECT * FROM Times', PredicateOperator::TYPE_SELECT);
     *
     * @param string $method
     * @param array $args
     * @return mixed|ResultSet
     * @throws
     */
    public function __call($method, $args)
    {
        if (strlen($method) > 6) {
            $func = strtolower($method);
            if ('findby' === substr($func, 0, 6)) {
                if (!isset($args[0])) {
                    throw TableGatewayException('findBy{Field} method require atleast one parameter');
                }
                $predicate = new PredicateOperator(substr($func, 6));

                if (isset($args[1])) {
                    $predicate->setOperator($args[0]);
                    $predicate->setRight($args[1]);

                    if (isset($args[2])) {
                        $predicate->setRightType($args[2]);
                    }
                }
                else {
                    $predicate->setRight($args[0]);
                }
                $where = new Where();
                return $this->select($where->addPredicate($predicate));
            }
        }
        return parent::__call($method, $args);
    }

    /**
     * @param $table
     * @param array $data
     * @return bool
     */
    protected function multiInsert ($table, array $data)
    {
        if (count($data)) {
            $columns = (array)current($data);
            $columns = array_keys($columns);
            $columnsCount = count($columns);
            $platform = $this->adapter->getPlatform();

            array_filter(
                $columns,
                function (&$item) use ($platform) {
                    $item = $platform->quoteIdentifier($item);
                }
            );
            $columns = "(" . implode(',', $columns) . ")";

            $placeholder = array_fill(0, $columnsCount, '?');
            $placeholder = "(" . implode(',', $placeholder) . ")";
            $placeholder = implode(',', array_fill(0, count($data), $placeholder));

            $values = array();
            foreach ($data as $row) {
                foreach ($row as $value) {
                    $values[] = $value;
                }
            }

            $table = $platform->quoteIdentifier($table);
            $q = sprintf('INSERT INTO %s %s VALUES %s', $table, $columns, $placeholder);
            return $this->adapter->query($q)->execute($values);
        }

        return false;
    }

    /**
     * @param $where
     *
     * @return int
     */
    public function deleteItem ($where)
    {
        return $this->delete($where);
    }
}