<?php

namespace PpitEquipment\Controller;

use PpitEquipment\Model\Area;
use PpitCore\Model\Csrf;
use PpitCore\Model\Context;
use PpitCore\Form\CsrfForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class AreaController extends AbstractActionController
{
    public function indexAction()
    {    	
    	// Retrieve the context
    	$context = Context::getCurrent();

    	$view = new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }

    public function listAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	// Filter
		$filter = array();
    	$caption = $this->params()->fromQuery('caption_search', NULL);
    	if ($caption) $filter['equipment_area.caption'] = $caption;

		// Sort
    	$major = $this->params()->fromQuery('major', NULL);
    	if (!$major) $major = 'caption';
    	$dir = $this->params()->fromQuery('dir', NULL);
    	if (!$dir) $dir = 'ASC';
    
		$areas = Area::getList($major, $dir, $filter);
    
    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    			'major' => $major,
    			'dir' => $dir,
    			'areas' => $areas,
    	));
    	$view->setTerminal(true);
    	return $view;
    }

    public function dataListAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	// Filter
    	$filter = array();
    	$caption = $this->params()->fromQuery('caption', NULL);
    	if ($caption) $filter['caption'] = $caption;
    
    	// Sort
    	$major = $this->params()->fromQuery('major', NULL);
    	if (!$major) $major = 'caption';
    	$dir = $this->params()->fromQuery('dir', NULL);
    	if (!$dir) $dir = 'ASC';
    
		$areas = Area::getList($major, $dir, $filter);
       	$data = array();
    	foreach ($areas as $area) {
    		$data[] = array(
    				'id' => $area->id,
    				'caption' => $area->caption,
    		);
    	}
    	return new JsonModel(array('data' => $data));
    }

    public function detailAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) $this->redirect()->toRoute('index');

    	$area = Area::get($id);
    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    			'area' => $area,
    			'id' => $id,
    	));
    	if ($context->isSpaMode()) $view->setTerminal(true);
    	return $view;
    }

    public function updateAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();

    	$id = (int) $this->params()->fromRoute('id', 0);
    	if ($id) $area = Area::get($id);
    	else $area = Area::instanciate();
    	
    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');
    	$message = null;
    	$error = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    
    		if ($csrfForm->isValid()) { // CSRF check
    			$area->loadDataFromRequest($request);

    			// Atomicity
    			$connection = Area::getTable()->getAdapter()->getDriver()->getConnection();
    			$connection->beginTransaction();
    			try {
		    		// Add or update
    				if (!$id) $return = $area->add();
    				else $return = $area->update($request->getPost('update_time'));
   					if ($return != 'OK') {
	    				$connection->rollback();
						$error = $return;
					}
					else {
						$connection->commit();
						$message = $return;
					}
    			}
           	    catch (\Exception $e) {
	    			$connection->rollback();
	    			throw $e;
	    		}
    		}
    	}
    	$view = new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'area' => $area,
       			'id' => $id,
    			'csrfForm' => $csrfForm,
    			'message' => $message,
    			'error' => $error
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }
    
	public function deleteAction()
    {
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) return $this->redirect()->toRoute('index');

    	// Retrieve the context
    	$context = Context::getCurrent();

    	// Retrieve the organizational unit
		$area = Area::get($id);
    	
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$message = null;
		$error = null;
    	// Retrieve the user validation from the post
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		
    		if ($csrfForm->isValid()) {

    			// Atomicity
    			$connection = Area::getTable()->getAdapter()->getDriver()->getConnection();
    			$connection->beginTransaction();
    			try {
		    		// Delete the row
					$return = $area->delete($request->getPost('update_time'));
					if ($return != 'OK') {
						$connection->rollback();
						$error = $return;
					}
					else {
						$connection->commit();
						$message = $return;
					}
    			}
           	    catch (\Exception $e) {
	    			$connection->rollback();
	    			throw $e;
	    		}
    		}  
    	}
    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'area' => $area,
    		'id' => $id,
    		'csrfForm' => $csrfForm,
    		'message' => $message,
    		'error' => $error,
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }
}
    