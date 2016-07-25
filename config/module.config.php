<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'PpitEquipment\Controller\Area' => 'PpitEquipment\Controller\AreaController',
            'PpitEquipment\Controller\Equipment' => 'PpitEquipment\Controller\EquipmentController',
        ),
    ),
 
    'router' => array(
        'routes' => array(
            'index' => array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'PpitEquipment\Controller\Area',
                        'action'     => 'index',
                    ),
                ),
           		'may_terminate' => true,
	       		'child_routes' => array(
	                'index' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/index',
	                    	'defaults' => array(
	                    		'action' => 'index',
	                        ),
	                    ),
	                ),
	       		),
            ),
        	'area' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/area',
                    'defaults' => array(
                        'controller' => 'PpitEquipment\Controller\Area',
                        'action'     => 'index',
                    ),
                ),
           		'may_terminate' => true,
	       		'child_routes' => array(
	       			'delete' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/delete[/:id]',
		                    'constraints' => array(
		                    	'id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'delete',
	                        ),
	                    ),
	                ),
	       			'index' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/index',
	                    	'defaults' => array(
	                    		'action' => 'index',
	                        ),
	                    ),
	                ),
	       			'list' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/list',
	                    	'defaults' => array(
	                    		'action' => 'list',
	                        ),
	                    ),
	                ),
	       			'dataList' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/data-list',
	                    	'defaults' => array(
	                    		'action' => 'dataList',
	                        ),
	                    ),
	                ),
	       			'update' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/update[/:id]',
		                    'constraints' => array(
		                    	'id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'update',
	                        ),
	                    ),
	                ),
	       			'detail' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/detail[/:id]',
		                    'constraints' => array(
		                    	'id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'detail',
	                        ),
	                    ),
	                ),
	       		),
        	),
        	'equipment' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/equipment',
                    'defaults' => array(
                        'controller' => 'PpitEquipment\Controller\Equipment',
                        'action'     => 'index',
                    ),
                ),
           		'may_terminate' => true,
	       		'child_routes' => array(
	       			'delete' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/delete[/:id]',
		                    'constraints' => array(
		                    	'id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'delete',
	                        ),
	                    ),
	                ),
	       			'index' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/index',
	                    	'defaults' => array(
	                    		'action' => 'index',
	                        ),
	                    ),
	                ),
	       			'list' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/list[/:area_id]',
		                    'constraints' => array(
		                    	'area_id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                    		'action' => 'list',
	                        ),
	                    ),
	                ),
	       			'dataList' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/data-list',
	                    	'defaults' => array(
	                    		'action' => 'dataList',
	                        ),
	                    ),
	                ),
	       			'add' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/add[/:area_id][/:product_category_id]',
		                    'constraints' => array(
		                    	'area_id' => '[0-9]*',
		                    	'product_category_id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'add',
	                        ),
	                    ),
	                ),
	       			'update' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/update[/:area_id][/:product_category_id][/:id]',
		                    'constraints' => array(
		                    	'area_id' => '[0-9]*',
		                    	'product_category_id' => '[0-9]*',
		                    	'id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'update',
	                        ),
	                    ),
	                ),
	       			'lock' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/lock[/:type][/:id]',
		                    'constraints' => array(
		                    	'type' => '[a-z]*',
		                    	'id' => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'lock',
	                        ),
	                    ),
	                ),
	       		),
        	),
        ),
    ),
	'bjyauthorize' => array(
		// Guard listeners to be attached to the application event manager
		'guards' => array(
			'BjyAuthorize\Guard\Route' => array(

				array('route' => 'area', 'roles' => array('user')),
				array('route' => 'area/dataList', 'roles' => array('user')),
				array('route' => 'area/delete', 'roles' => array('admin')),
				array('route' => 'area/index', 'roles' => array('user')),
				array('route' => 'area/list', 'roles' => array('user')),
				array('route' => 'area/detail', 'roles' => array('admin')),
				array('route' => 'area/update', 'roles' => array('admin')),

				array('route' => 'equipment', 'roles' => array('user')),
				array('route' => 'equipment/dataList', 'roles' => array('user')),
				array('route' => 'equipment/delete', 'roles' => array('admin')),
				array('route' => 'equipment/index', 'roles' => array('user')),
				array('route' => 'equipment/list', 'roles' => array('user')),
				array('route' => 'equipment/add', 'roles' => array('admin')),
				array('route' => 'equipment/update', 'roles' => array('admin')),
				array('route' => 'equipment/lock', 'roles' => array('admin')),
			)
		)
	),
		
    'view_manager' => array(
    	'strategies' => array(
    			'ViewJsonStrategy',
    	),
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',       // On défini notre doctype
        'not_found_template'       => 'error/404',   // On indique la page 404
        'exception_template'       => 'error/index', // On indique la page en cas d'exception
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
        ),
        'template_path_stack' => array(
            'ppit-equipment' => __DIR__ . '/../view',
        ),
    ),
	'translator' => array(
		'locale' => 'fr_FR',
		'translation_file_patterns' => array(
			array(
				'type'     => 'phparray',
				'base_dir' => __DIR__ . '/../language',
				'pattern'  => '%s.php',
				'text_domain' => 'ppit-equipment'
			),
	       	array(
	            'type' => 'phpArray',
	            'base_dir' => './vendor/zendframework/zendframework/resources/languages/',
	            'pattern'  => 'fr/Zend_Validate.php',
	        ),
 		),
	),
	'ppitEquipmentDependencies' => array(
	),
);
