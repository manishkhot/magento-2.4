<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_StoreLocator
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */
namespace Lof\StoreLocator\Model;

class Category extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const CACHE_TAG = 'lof_storelocator_category';
    
    protected $_storeManager;
    protected $_url;
    protected $_storelocatorHelper;
    protected $_resource;
 
    
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Lof\StoreLocator\Model\ResourceModel\Category $resource = null,
        \Lof\StoreLocator\Model\ResourceModel\Category\Collection $resourceCollection = null,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $url,
        \Lof\StoreLocator\Helper\Data $storelocatorHelper,
        array $data = []
        ) {
        $this->_storeManager = $storeManager;
        $this->_url = $url;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_resource = $resource;
        $this->_storelocatorHelper = $storelocatorHelper;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Lof\StoreLocator\Model\ResourceModel\Category');
    }

    /**
     * Prevent blocks recursion
     *
     * @return \Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $needle = 'category_id="' . $this->getId() . '"';
        if (false == strstr($this->getContent(), $needle)) {
            return parent::beforeSave();
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            __('Make sure that category content does not reference the block itself.')
            );
    }

    public function getCreateTime(){
        $dateTime = $this->getData('create_time');
        $dateFormat = $this->_storelocatorHelper->getConfig('general/dateformat');
        return $this->_storelocatorHelper->getFormatDate($dateTime, $dateFormat);
    }

    /**
     * Receive page store ids
     *
     * @return int[]
     */
    public function getStores()
    {
        return $this->hasData('stores') ? $this->getData('stores') : $this->getData('store_id');
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    public function getResourceModelObject(){
        if(!$this->_resource){
            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_resource = $object_manager->create("Lof\StoreLocator\Model\ResourceModel\Category");
        }
        return $this->_resource;
    }

    public function getListCategoryNameByIds($ids){

        $readConnection = $this->getResourceModelObject()->getConnection('core_read');

        if (is_array($ids)) {
            $ids = implode(",", $ids);
        }
        $ids = str_replace("on,","", $ids);
        $where = 'category_id IN('.$ids.')';
        $query = 'SELECT name FROM ' . $this->getResourceModelObject()->getTable('lof_storelocator_storelocator_category') . ' WHERE ' . $where;

        $results = $readConnection->fetchAll($query);

        $data = array();

        foreach ($results as $key => $result) {
            $data[$key] = $result['name'];
        }

        return implode(",", $data);
    }

    public function selectNameIN($names){
        $readConnection = $this->getResourceModelObject()->getConnection('core_read');
        $query = $this->getCollection()->getSelect('name')->where('name IN (?) ', explode(',',$names));
        $results = $readConnection->fetchAll($query);

        $data = array();

        foreach ($results as $result) {
            $data[] = $result['category_id'];
        }

        return $data;
    }
    public function selectCatsByNameIN($names){
        $readConnection = $this->getResourceModelObject()->getConnection('core_read');
        $query = $this->getCollection()->getSelect('name')->where('name IN (?) ', explode(',',$names));
        $results = $readConnection->fetchAll($query);

        $data = array();

        foreach ($results as $result) {
            $data[$result['name']] = $result;
        }

        return $data;
    }
    public function importData($import_data = array()) {
        if(isset($import_data['name']) && $import_data['name']){

            $category_name_array = explode(",",$import_data['name']);
            $category_description_array = isset($import_data['description'])?explode("|",$import_data['description']):array();
            $category_position_array = isset($import_data['position'])?explode("|",$import_data['position']):array();
            $category_array = $this->selectCatsByNameIN($import_data['name']);

            if($category_name_array) {
                foreach($category_name_array as $k=>$v){
                    $category_data = array();
                    if(isset($category_array[$v])){
                        $category_data = $category_array[$v];
                    }
                    $category_data['name'] = $v;
                    if(isset($category_description_array[$k]) && $category_description_array[$k]) {
                        $category_data['description'] = $category_description_array[$k];
                    }
                    if(isset($category_position_array[$k]) && $category_position_array[$k]) {
                        $category_data['position'] = (int)$category_position_array[$k];
                    }

                    $model = clone $this;
                    if(isset($category_data['category_id'])){
                        $model->load($category_data['category_id']);
                    }

                    $model->setData($category_data);
                    $model->save();
                }
            }
        }
        return true;
    }
}
