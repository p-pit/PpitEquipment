<?php
namespace PpitEquipment\Model;

use PpitCore\Model\Context;
use PpitEquipment\Model\Area;
use PpitMasterData\Model\Product;
use PpitMasterData\Model\ProductCategory;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Equipment implements InputFilterAwareInterface
{
    public $id;
    public $area_id;    
    public $product_category_id;
    public $product_id;
    public $lock;
    public $what_if_action;
    public $target_area_id;
    public $target_product_id;
    public $rent;
    public $identifier;
    public $asset_identifier;
    public $exploitation_date;
    public $place;
    public $options = array();
    public $properties = array();
    public $update_time;

    // Deprecated
    public $caption;
    public $brand;
    public $model;

    // Joined properties
    public $product_category_caption;
    public $product_brand;
    public $product_caption;
    public $option_price;
    public $matrix;
    
    // Transient properties
    
	public $availableProductCategories;
    public $availableAreas;
    public $area;
    public $target_area_caption;
    public $availableProducts;
    public $availableOptions;
    public $targetProduct;
    public $target_rent;
    public $option_rent;
    public $including_option_rent;
    
    protected $inputFilter;

    // Static fields
    private static $table;
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->area_id = (isset($data['area_id'])) ? $data['area_id'] : null;
        $this->product_category_id = (isset($data['product_category_id'])) ? $data['product_category_id'] : null;
        $this->product_id = (isset($data['product_id'])) ? $data['product_id'] : null;
        $this->lock = (isset($data['lock'])) ? $data['lock'] : null;
        $this->what_if_action = (isset($data['what_if_action'])) ? $data['what_if_action'] : null;
        $this->target_area_id = (isset($data['target_area_id'])) ? $data['target_area_id'] : null;
        $this->target_product_id = (isset($data['target_product_id'])) ? $data['target_product_id'] : null;
        $this->rent = (isset($data['rent'])) ? $data['rent'] : null;
        $this->identifier = (isset($data['identifier'])) ? $data['identifier'] : null;
        $this->asset_identifier = (isset($data['asset_identifier'])) ? $data['asset_identifier'] : null;
        $this->exploitation_date = (isset($data['exploitation_date'])) ? $data['exploitation_date'] : null;
        $this->place = (isset($data['place'])) ? $data['place'] : null;
        $this->options = (isset($data['options'])) ? json_decode($data['options'], true) : null;
        $this->properties = (isset($data['properties'])) ? json_decode($data['properties']) : null;
        $this->update_time = (isset($data['update_time'])) ? $data['update_time'] : null;
        
        // Deprecated
        $this->caption = (isset($data['caption'])) ? $data['caption'] : null;
        $this->brand = (isset($data['brand'])) ? $data['brand'] : null;
        $this->model = (isset($data['model'])) ? $data['model'] : null;

        // Additional properties from joined tables
        $this->product_category_caption = (isset($data['product_category_caption'])) ? $data['product_category_caption'] : null;
        $this->product_brand = (isset($data['product_brand'])) ? $data['product_brand'] : null;
        $this->product_caption = (isset($data['product_caption'])) ? $data['product_caption'] : null;
    }

    public function toArray()
    {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['area_id'] = (int) $this->area_id;
    	$data['product_category_id'] = (int) $this->product_category_id;
    	$data['product_id'] = (int) $this->product_id;
    	$data['lock'] = (int) $this->lock;
    	$data['what_if_action'] = $this->what_if_action;
    	$data['target_area_id'] = (int) $this->target_area_id;
    	$data['target_product_id'] = (int) $this->target_product_id;
    	$data['rent'] = (float) $this->rent;
    	$data['identifier'] = $this->identifier;
    	$data['asset_identifier'] = $this->asset_identifier;
    	$data['exploitation_date'] = $this->exploitation_date;
    	$data['place'] = $this->place;
	    $data['options'] = json_encode($this->options);
    	$data['properties'] = json_encode($this->properties);

    	// Deprecated
    	$data['caption'] = $this->caption;
    	$data['brand'] = $this->brand;
    	$data['model'] = $this->model;

    	return $data;
    }

    public static function get($id)
    {
    	$context = Context::getCurrent();
		$equipment = Equipment::getTable()->get($id);

		// Retrieve the area
		$equipment->area = Area::get($equipment->area_id);

		// Retrieve the product
		$product = Product::get($equipment->product_id);
		$equipment->product_caption = $product->caption;
		$equipment->product_brand = $product->brand;
		$equipment->availableOptions = $product->optionList;
    	$equipment->availableAreas = Area::retrievePerimeter($context->getContactId());
    	$equipment->availableProducts = Product::getListForOrder($context->getCommunityId(), array($equipment->product_category_id => null));
		if ($equipment->target_area_id) $equipment->target_area_caption = Area::getTable()->get($equipment->target_area_id)->caption;
		if ($equipment->target_product_id) {
			$targetProduct = Product::getTable()->get($equipment->target_product_id);
			$equipment->target_product = Product::getTable()->get($equipment->target_product_id);
		}

		return $equipment;
    }

    public static function instantiate($area_id, $product_category_id)
    {
    	$context = Context::getCurrent();
    	$equipment = new Equipment;
    	$equipment->area_id = $area_id;
    	$equipment->product_category_id = $product_category_id;
    	 
    	$productCategories = array();
    	if ($product_category_id) $productCategories[$product_category_id] = null;
    	else {
	    	// Retrieve the available product categories
	    	$area = Area::getTable()->get($area_id);
			$select = ProductCategory::getTable()->getSelect()->order(array('caption'));
			$cursor = ProductCategory::getTable()->selectWith($select);
			foreach ($cursor as $productCategory) {
				if (array_key_exists($productCategory->id, $area->policy)) {
					$productCategories[$productCategory->id] = $productCategory->caption;
				}
			}
    	}
    	$equipment->availableProductCategories = $productCategories;
		$equipment->availableOptions = array();
    	$equipment->availableAreas = Area::retrievePerimeter($context->getContactId());
    	$equipment->availableProducts = Product::getListForOrder($context->getCommunityId(), $equipment->availableProductCategories);
    	return $equipment;
    }
    
    public function loadData($data)
    {
    	$context = Context::getCurrent();
    
    	$this->target_area_id = null;
		$this->target_product_id = null;
		$this->what_if_action = trim(strip_tags($data['what_if_action']));
    	if ($this->what_if_action == 'add') {
	    	$this->product_id = (int) $data['product_id'];
			if (!$this->product_id) return 'Integrity';
    		$product = Product::getTable()->get($this->product_id);
    		if (!$product) return 'Integrity';
    		$this->product = $product;
    		$this->rent = $product->prices[$this->area->place->tax_regime];
	    	$this->product_category_id = $product->product_category_id;
    	}
	    elseif ($this->what_if_action == 'move') {
	    	$this->target_area_id = (int) $data['target_area_id'];
			if (!$this->target_area_id) return 'Integrity';
	    }
	    elseif ($this->what_if_action == 'change') {
	    	$this->target_product_id = (int) $data['target_product_id'];
    		if (!$this->target_product_id) return 'Integrity';
    		$product = Product::getTable()->get($this->target_product_id);
    		if (!$product) return 'Integrity';
	    }
	    elseif ($this->what_if_action == 'reset') $this->what_if_action = null;

	    return 'OK';
    }
    
    public function loadDataFromRequest($request)
    {
    	$data = array();
    	$data['what_if_action'] = $request->getPost('what_if_action');
		if ($data['what_if_action'] == 'add') {
    		$data['product_id'] = $request->getPost('product_id');
       	}
       	elseif ($data['what_if_action'] == 'move') {
		    $data['target_area_id'] = $request->getPost('target_area_id');
	   	}
	   	elseif ($data['what_if_action'] == 'change') {
		    $data['target_product_id'] = $request->getPost('target_product_id');
	   	}
    
    	$return = $this->loadData($data);
    	if ($return != 'OK') throw new \Exception('View error');
    	return $return;
    }

    public function add()
    {
    	$context = Context::getCurrent();
    
    	Equipment::getTable()->save($this);
    
    	return 'OK';
    }
    
    public function update($update_time)
    {
    	$context = Context::getCurrent();
    	$equipment = Equipment::get($this->id);
    
    	// Isolation check
    	if ($equipment->update_time > $update_time) return 'Isolation';
    
    	Equipment::getTable()->save($this);
    
    	return 'OK';
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getInputFilter()
    {
        throw new \Exception("Not used");
    }

    public static function getTable()
    {
    	if (!Equipment::$table) {
    		$sm = Context::getCurrent()->getServiceManager();
    		Equipment::$table = $sm->get('PpitEquipment\Model\EquipmentTable');
    	}
    	return Equipment::$table;
    }
}
