<?php
namespace PpitEquipment;

use PpitEquipment\Model\Area;
use PpitEquipment\Model\Equipment;
use PpitCore\Model\GenericTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
 	          	'PpitEquipment\Model\AreaTable' =>  function($sm) {
                    $tableGateway = $sm->get('AreaTableGateway');
                    $table = new GenericTable($tableGateway);
                    return $table;
                },
                'AreaTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Area());
                    return new TableGateway('equipment_area', $dbAdapter, null, $resultSetPrototype);
                },
 	          	'PpitEquipment\Model\EquipmentTable' =>  function($sm) {
                    $tableGateway = $sm->get('EquipmentTableGateway');
                    $table = new GenericTable($tableGateway);
                    return $table;
                },
                'EquipmentTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Equipment());
                    return new TableGateway('equipment', $dbAdapter, null, $resultSetPrototype);
                },
            ),
        );
    }
}
