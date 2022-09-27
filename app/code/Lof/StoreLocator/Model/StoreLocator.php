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

class StoreLocator extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Blog's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /**
     * URL Model instance
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_storelocatorHelper;

    protected $_resource;
    /**
     * Page cache tag
     */
    const CACHE_TAG = 'lof_storelocator_storelocator';
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Lof\StoreLocator\Model\ResourceModel\StoreLocator $resource = null,
        \Lof\StoreLocator\Model\ResourceModel\StoreLocator\Collection $resourceCollection = null,
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
        $this->_init('Lof\StoreLocator\Model\ResourceModel\StoreLocator');
    }

    /**
     * Prevent blocks recursion
     *
     * @return \Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $needle = 'storelocator_id="' . $this->getId() . '"';
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


    public function getTag()
    {
        return $this->hasData('tag') ? $this->getData('tag') : $this->getData('tag');
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

    

    public function importData($import_data = array()) {
        if(isset($import_data['name']) && $import_data['name']){

            $model = clone $this;
            if(isset($import_data['id'])){
                $model->load($import_data['id']);
                unset($import_data['id']);
            }
            if((!isset($import_data['stores'])) || (isset($import_data['stores']) && !isset($import_data['stores']))){

                $import_data['stores'] = array("0");
                if(isset($import_data['store_id']) && $import_data['store_id']){
                    $import_data['stores'] = explode(",",$import_data['store_id']);
                }
            }
            if($import_data){
                foreach($import_data as $key=>$val){
                    $model->setData($key,$val);
                }
            }
            $model->save();
        }
        return true;
    }
}
