<?php
namespace PpitEquipment\Model;

use PpitCore\Model\Context;
use PpitCore\Model\Generic;
use PpitEquipment\Model\Equipment;
use PpitMasterData\Model\OrgUnit;
use PpitMasterData\Model\Place;
use PpitMasterData\Model\ProductCategory;
use PpitMasterData\Model\Product;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Area implements InputFilterAwareInterface
{
    public $id;
    public $org_unit_id;
    public $caption;
    public $properties = array();
    public $policy = array();
	public $has_what_if;
    public $update_time;
    
    // Joined properties
    public $site_identifier;
    public $site_caption;
    
    // Transient properties
    public $org_unit_identifier;
    public $place;
    public $vat_rate;
    public $availableProperties;
    public $policyEquipments;
    public $policySlots;
    public $extraPolicyEquipments;

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
        $this->org_unit_id = (isset($data['org_unit_id'])) ? $data['org_unit_id'] : null;
        $this->caption = (isset($data['caption'])) ? $data['caption'] : null;
        $this->properties = (isset($data['properties'])) ? json_decode($data['properties'], true) : null;
        $this->policy = (isset($data['policy'])) ? json_decode($data['policy'], true) : null;
        $this->has_what_if = (isset($data['has_what_if'])) ? $data['has_what_if'] : null;
        $this->update_time = (isset($data['update_time'])) ? $data['update_time'] : null;

        // Joined properties
        $this->site_identifier = (isset($data['site_identifier'])) ? $data['site_identifier'] : null;
        $this->site_caption = (isset($data['site_caption'])) ? $data['site_caption'] : null;
    }
    
    public function toArray()
    {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['org_unit_id'] = (int) $this->org_unit_id;
    	$data['caption'] =  $this->caption;
    	$data['properties'] = json_encode($this->properties);
    	$data['policy'] = json_encode($this->policy);
    	$data['has_what_if'] =  (int) $this->has_what_if;
    	return $data;
    }

    public static function getList($major = 'equipment_area.caption', $dir = '', $filter = array())
    {
    	$select = Area::getTable()->getSelect()
    		->join('md_org_unit', 'equipment_area.org_unit_id = md_org_unit.id', array('site_identifier' => 'identifier', 'site_caption' => 'caption'), 'left')
    		->order(array($major.' '.$dir, 'equipment_area.caption'));
    	$where = new Where;
    	foreach ($filter as $property => $value) {
    		$where->like($property, '%'.$value.'%');
    	}
    	$select->where($where);
    	$cursor = Area::getTable()->selectWith($select);
    	$areas = array();
    	foreach ($cursor as $area) $areas[$area->id] = $area;
    	return $areas;
    }

    public static function retrievePerimeter($contact_id/*, $role_id*/)
    {
		$areas = Area::getList();

    	// Retrieve the organizational units the current contact is authorized on for the given role
    	$orgUnits = OrgUnit::retrievePerimeter($contact_id/*, $role_id*/);

    	// Retrieve the depending areas
    	$perimeter = array();
    	foreach ($areas as $area) {
    		if (array_key_exists($area->org_unit_id, $orgUnits)) $perimeter[$area->id] = $area;
    	}
    	return $perimeter;
    }

    public static function get($id)
    {
    	$context = Context::getCurrent();
    	$area = Area::getTable()->get($id);
    	$orgUnit = OrgUnit::getTable()->get($area->org_unit_id);
    	$place = Place::getTable()->get($orgUnit->place_id);
    	$area->place = $place;
    	$area->site_identifier = $orgUnit->identifier;
    	$area->site_caption = $orgUnit->caption;
		$area->org_unit_identifier = $area->site_identifier.' - '.$area->site_caption;
    	$area->availableProperties = $context->getConfig()['ppitEquipmentSettings']['areaProperties'];
    	$area->vat_rate = $context->getConfig()['ppitMasterDataSettings']['vatRates'][$place->tax_regime];
    	return $area;
    }

    public function retrieveEquipments() {

		// Retrieve the equipments
    	$this->policyEquipments = array();
    	$this->policySlots = array();
    	$this->extraPolicyEquipments = array();
    	$select = Equipment::getTable()->getSelect()
    		->join('md_product_category', 'equipment.product_category_id = md_product_category.id', array('product_category_caption' => 'caption'), 'left')
    		->join('md_product', 'equipment.product_id = md_product.id', array('product_brand' => 'brand', 'product_caption' => 'caption'), 'left')
    		->where(array('equipment.area_id' => $this->id))
	    	->order(array('product_category_id', 'caption DESC'));
        $cursor = Equipment::getTable()->selectWith($select);
        foreach ($cursor as $equipment) {
        	
        	// Case of a current what-if for replacing an equipment
        	if ($equipment->target_product_id) {
        		$equipment->targetProduct = Product::get($equipment->target_product_id);
        		$equipment->target_rent = $equipment->targetProduct->prices[$this->place->tax_regime];
        	}
        	
        	if (array_key_exists($equipment->product_category_id, $this->policy) && $this->policy[$equipment->product_category_id] > 0) {
	        	$this->policyEquipments[$equipment->id] = $equipment;
	        	$this->policy[$equipment->product_category_id]--;
        	}
        	else $this->extraPolicyEquipments[$equipment->id] = $equipment;
        	$equipment->option_rent = 0;
    		foreach ($equipment->options as $option) $equipment->option_rent += $option['rent'];
    		$equipment->including_option_rent = $equipment->rent + $equipment->option_rent;
        }
        $i = 0;
        foreach ($this->policy as $product_category_id => $slots) {
        	$slots = (int) $slots;
        	$productCategory = ProductCategory::getTable()->get($product_category_id);
        	for (; $slots > 0; $slots--) {
        		$this->policySlots[$i] = $productCategory;
        		$i++;
        	}
        }
    }
    
    public static function instanciate($orgUnit)
    {
    	$area = new Area;
    	$area->org_unit_id = $orgUnit->id;
    	$area->site_identifier = $orgUnit->identifier;
    	$area->site_caption = $orgUnit->caption;
    	$area->availableProperties = $context->getConfig()['ppitEquipmentSettings']['areaProperties'];
    	return $area;
    }
    
    public function loadData($data)
    {
    	$context = Context::getCurrent();
    	$this->org_unit_id = (int) $data['org_unit_id'];
    	$this->org_unit_identifier = trim(strip_tags($data['org_unit_identifier']));
    	$this->caption = trim(strip_tags($data['caption']));
    	foreach ($context->getConfig()['ppitEquipmentSettings']['areaProperties'] as $property => $params) {
    		$this->properties[$property] = $data[$property];
    	}

    	// Check integrity
    	$orgUnit = OrgUnit::getTable()->get($this->org_unit_id);
    	if (!$orgUnit || $orgUnit->type != 'Site') return 'Integrity';
    	if ($this->caption == '' || strlen($this->caption) > 255) return 'Integrity';
    	return 'OK';
    }

    public function loadDataFromRequest($request)
    {
    	$context = Context::getCurrent();
		$data = array();
		$data['org_unit_id'] = $request->getPost('org_unit_id');
		$data['org_unit_identifier'] = $request->getPost('org_unit_identifier');
		$data['caption'] = $request->getPost('caption');
    	foreach ($context->getConfig()['ppitEquipmentSettings']['areaProperties'] as $property => $params) {
			$data[$property] = $request->getPost($property);
		}
		if ($this->loadData($data) != 'OK') throw new \Exception('View error');
    }

    public function add()
    {
    	$context = Context::getCurrent();
    
    	// Check consistency
    	if (Generic::getTable()->cardinality('equipment_area', array('org_unit_id' => $this->org_unit_id, 'caption' => $this->caption)) > 0) return 'Duplicate';

    	$this->id = null;
    	Equipment::getTable()->save($this);

    	return ('OK');
    }
    
    public function update($update_time)
    {
    	$area = Area::get($this->id);
    
    	// Isolation check
    	if ($area->update_time > $update_time) return 'Isolation';
    
    	Area::getTable()->save($this);
    
    	return 'OK';
    }

    public function isUsed($object)
    {
    	// Allow or not deleting an organizational unit
    	if (get_class($object) == 'PpitMasterData\Model\OrgUnit') {
    		if (Area::getTable()->get($object->org_unit_id)) {
    			return true;
    		}
    	}
    	return false;
    }
    
    public function isDeletable() {

    	// Check dependency on equipments
//    	if (Generic::getTable()->cardinality('equipment', array('area_id' => $this->id)) > 0) return false;

    	return true;
    }

    public function delete($update_time)
    {
    	$context = Context::getCurrent();
    	$area = Area::get($this->id);
    
    	// Isolation check
    	if ($area->update_time > $update_time) return 'Isolation';
    
    	Area::getTable()->delete($this->id);
    
    	return 'OK';
    }
    
    // Add content to this method:
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
    	if (!Area::$table) {
    		$sm = Context::getCurrent()->getServiceManager();
    		Area::$table = $sm->get('PpitEquipment\Model\AreaTable');
    	}
    	return Area::$table;
    }
}