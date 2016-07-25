<?php
namespace PpitEquipment\Controller;

use PpitCore\Form\CsrfForm;
use PPitCore\Model\Context;
use PpitCore\Model\Csrf;
use PpitEquipment\Model\Area;
use PpitEquipment\Model\Equipment;
use PpitOrder\Model\Order;
use PpitOrder\Model\OrderProduct;
use PpitOrder\Model\OrderProductOption;
use PpitOrder\Model\OrderWithdrawal;
use PpitMasterData\Model\Product;
use PpitMasterData\Model\ProductOption;
use PpitMasterData\Model\ProductOptionMatrix;
use PpitPil\Model\Workflow;
use Zend\Session\Container;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EquipmentController extends AbstractActionController
{
	public function listAction()
	{
		// Retrieve the area
		$area_id = (int) $this->params()->fromRoute('area_id', 0);
		if (!$area_id) {
			return $this->redirect()->toRoute('index');
		}
		// Retrieve the context
		$context = Context::getCurrent();
		
		// Retrieve the area
		$area = Area::get($area_id);
		$area->retrieveEquipments();

		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'area' => $area,
		));
		if ($context->isSpaMode()) $view->setTerminal(true);
		return $view;
	}

	public function addAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();

		// Retrieve the area
		$area_id = (int) $this->params()->fromRoute('area_id', 0);
		$area = Area::get($area_id);
	
		// Retrieve the product category
		$product_category_id = (int) $this->params()->fromRoute('product_category_id', 0);
		$equipment = Equipment::instantiate($area_id, $product_category_id);
		$equipment->area = $area;
	
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$error = null;
		$message = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
			$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
			$csrfForm->setData($request->getPost());
			 
			if ($csrfForm->isValid()) { // CSRF check
	
				// Retrieve the data from the request
				$equipment->loadDataFromRequest($request);
				 
				// Atomicity
				$connection = Equipment::getTable()->getAdapter()->getDriver()->getConnection();
				$connection->beginTransaction();
				try {
					$return = $equipment->add();
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
				'area_id' => $area_id,
				'area' => $area,
				'product_category_id' => $product_category_id,
				'equipment' => $equipment,
				'csrfForm' => $csrfForm,
				'error' => $error,
				'message' => $message
		));
		$view->setTerminal(true);
		return $view;
	}
	
	public function updateAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();

    	// Retrieve the area
    	$area_id = (int) $this->params()->fromRoute('area_id', 0);
    	$area = Area::get($area_id);
    	 
    	// Retrieve the product category
    	$product_category_id = (int) $this->params()->fromRoute('product_category_id', 0);
    	 
    	// Retrieve the equipment and action
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if ($id) $equipment = Equipment::get($id);
    	else $equipment = Equipment::instantiate($area_id, $product_category_id);

    	// Instanciate the csrf form
    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');
    	$error = null;
    	$message = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		 
    		if ($csrfForm->isValid()) { // CSRF check
    
    			// Retrieve the data from the request
    			$equipment->loadDataFromRequest($request);
    		    			
    			// Atomicity
    			$connection = Equipment::getTable()->getAdapter()->getDriver()->getConnection();
    			$connection->beginTransaction();
    			try {
		    		$return = $equipment->update($request->getPost('update_time'));
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
    			'area_id' => $area_id,
    			'area' => $area,
    			'product_category_id' => $product_category_id,
    			'id' => $id,
    			'equipment' => $equipment,
    			'csrfForm' => $csrfForm,
    			'error' => $error,
    			'message' => $message
    	));
		$view->setTerminal(true);
       	return $view;
    }

    public function lockAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();

    	// Retrieve the equipment and action
    	$id = (int) $this->params()->fromRoute('id', 0);
    	$type = $this->params()->fromRoute('type');
    	if (!$id) return $this->redirect()->toRoute('index');
    	$equipment = Equipment::get($id);

    	// Instanciate the csrf form
    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');
    	$error = null;
    	$message = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		 
    		if ($csrfForm->isValid()) { // CSRF check
    
    			// Retrieve the data from the request
    			$equipment->loadDataFromRequest(request, $action);
    		    			
    			// Atomicity
    			$connection = Equipment::getTable()->getAdapter()->getDriver()->getConnection();
    			$connection->beginTransaction();
    			try {
		    		$return = $equipment->update($request->getPost('update_time'));
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
    			'id' => $id,
    			'type' => $type,
    			'equipment' => $equipment,
    			'csrfForm' => $csrfForm,
    			'error' => $error,
    			'message' => $message
    	));
    	$view->setTerminal(true);
    	return $view;
    }

    public function deleteAction()
    {
		// Check the presence of the id parameter for the entity to delete
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('index');
    	}
		// Retrieve the context
		$context = Context::getCurrent();
    	
    	// Retrieve the order product row
    	$orderProduct = OrderProduct::getTable()->get($id);

    	// Retrieve the order
    	$order = Order::getTable()->get($orderProduct->order_id);
    	$order->retrieveProperties();

    	// Retrieve the product
    	$product = Product::getTable()->get($orderProduct->product_id);

    	// Instanciate the csrf form
    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');
    	$error = null;
    	$message = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());

    		if ($csrfForm->isValid()) { // CSRF check
				if ($orderProduct->update_time != $request->getPost('db_update_time')) $error = 'Isolation';
    			else {
	    
	    			OrderProduct::getTable()->delete($id);
	    			
	    			// Delete the workflow for this product
	    			$select = Workflow::getTable()->getSelect()
	    				->where(array('entity' => 'order_product', 'entity_id' => $id));
	    			$cursor = Workflow::getTable()->selectWith($select);
	    			foreach ($cursor as $step) Workflow::getTable()->delete($step->id);
	    			
	    			$message = 'OK';
    			}
    		}
    	}

    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'id' => $id,
    		'order' => $order,
    		'orderProduct' => $orderProduct,
    		'product' => $product,
    		'csrfForm' => $csrfForm,
    		'error' => $error,
    		'message' => $message
    	));
		if ($context->isSpaMode()) $view->setTerminal(true);
       	return $view;
    }
}
