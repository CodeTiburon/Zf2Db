<?php
/**
 * Edited by Nickolay U. Kofanov 07.06.2013
 */
namespace Zf2Db\Model;

abstract class AbstractModel
{
    public function exchangeArray($data)
    {
        __exchangeArray($this, $data);
    }

    public function getArrayCopy()
    {
        return __getArrayCopy($this);
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}


/**
 * Redefine `get_object_vars` for getting public variables only
 * because of `get_object_vars` called inside class scope would return all its fields (even private and protected)
 *
 * @param $obj object
 * @return array
 */
function __getArrayCopy($obj)
{
    return get_object_vars($obj);
}

/**
 * Go through public variables only, and assign value from corresponding array element
 *
 * @param $obj
 * @param $arr
 */
function __exchangeArray($obj, $arr)
{
    foreach($obj as $key => $val) {
        $obj->$key = isset($arr[$key]) ? $arr[$key] : null;
    }
}