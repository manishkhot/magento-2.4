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
namespace Lof\StoreLocator\Controller\Index; 

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Lof\StoreLocator\Model\ResourceModel\StoreLocator\CollectionFactory;
use Lof\StoreLocator\Helper\Image;

class Locations extends \Magento\Framework\App\Action\Action {


    protected $_resultPageFactory;
    protected $_storelocatorCollection;
    protected $_objectManager;
    protected $_helper;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $storelocatorCollection,
        Image $helper,
        \Lof\StoreLocator\Helper\Data $storeLocatorHelper
    ) {
        $this->_resultPageFactory      = $resultPageFactory;
        $this->_storelocatorCollection = $storelocatorCollection;
        $this->_helper                 = $helper;
        $this->_objectManager          = $context->getObjectManager();
        $this->storeLocatorHelper      = $storeLocatorHelper;
        parent::__construct($context);
    } 

    /**
     * Index
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getStoreId();

        $enable_rewrite_url = $this->storeLocatorHelper->getConfig('general/enable_rewrite_url');
        $route = $this->storeLocatorHelper->getConfig('general/route');
        $route = $route?$route."/":"storelocator/";
        $default_locator_image = $this->storeLocatorHelper->getConfig('general/default_locator_image');
        $default_locator_image = $default_locator_image?$default_locator_image:"lof/storelocator/default.png";
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Lof\StoreLocator\Model\StoreLocator');

        $maker_width = 100;
        $maker_height = 100;
        // 2. Initial checking
        if ($id) {
            $_jsonLocatorData = array();
            $model->load($id);
            if (!$model->getId()) {
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
            $result = $model->getData();
            unset($result['posts']);

            $url_store_name = urlencode($result['name']);
            $store_url = $this->_helper->getBaseUrl() . $route . str_replace(" ", "-", $url_store_name);
            if($enable_rewrite_url && isset($result['seo_url']) && $result['seo_url']) {
                $store_url = $this->_helper->getBaseUrl().$result['seo_url'];
            }
            if(!$result['image']){
                $result['image'] = $default_locator_image;
            }
            $_jsonLocatorData    =   array(
                'id'        =>  $result['storelocator_id'],
                'name'      =>  $result['name'],
                'lng'       =>  $result['lng'],
                'lat'       =>  $result['lat'],
                'address'   =>  $result['address'],
                'address2'  =>  $result['address2'],
                'link'      =>  isset($result['link'])?$result['link']:'',
                'image'     =>  $this->_helper->resizeImage($result['image'], 128, 128),
                'maker_icon'=>  $this->_helper->resizeImage($result['maker_icon'], $maker_width, $maker_height),
                'telephone' =>  $result['telephone'],
                'email'     =>  $result['email'],
                'website'   =>  isset($result['website'])?$result['website']:'',
                'city'      =>  $result['city'],
                'state'     =>  $result['state'],
                'country'   =>  $result['country'],
                'zipcode'   =>  $result['zipcode'],
                'hours'     =>  $result['hours'],
                'hours1'    =>  $result['hours1'],
                'hours2'    =>  $result['hours2'],
                'hours3'    =>  $result['hours3'],
                'hours4'    =>  $result['hours4'],
                'hours5'    =>  $result['hours5'],
                'hours6'    =>  $result['hours6'],
                'linkedin'  =>  $result['linkedin'],
                'facebook'  =>  $result['facebook'],
                'twitter'   =>  $result['twitter'],
                'youtube'   =>  $result['youtube'],
                'vimeo'     =>  $result['vimeo'],
                'tag'       =>  $result['tag'],
                'category'  =>  $result['category'],
                'href'      =>  $store_url,
                'color'     =>  $result['color'],
                'fontClass' =>  $result['fontClass']
                );
            echo json_encode(array($_jsonLocatorData));
            exit();
        }

        // 3. list data
        $_data = $this->_storelocatorCollection->create()->addFieldToFilter("is_active", 1)->addStoreFilter($storeId);
        $orderby = "ASC";
        $_data->getSelect()->order("main_table.position " . $orderby);
        $_locationData = $_data->getData();
        $_jsonLocationData = array();
        foreach ($_locationData as $result) {

        $url_store_name = urlencode($result['name']);
        $store_url = $this->_helper->getBaseUrl() . $route . str_replace(" ", "-", $url_store_name);
        if($enable_rewrite_url && isset($result['seo_url']) && $result['seo_url']) {
            $store_url = $this->_helper->getBaseUrl().$result['seo_url'];
        }
        if(!$result['image']){
                $result['image'] = $default_locator_image;
            }
         $_jsonLocationData[]    =   array(
            'id'        =>  $result['storelocator_id'],
            'name'      =>  $result['name'],
            'lng'       =>  $result['lng'],
            'lat'       =>  $result['lat'],
            'address'   =>  $result['address'],
            'address2'  =>  $result['address2'],
                'link'      =>  '', //$result['link'],
                'image'     =>  $this->_helper->resizeImage($result['image'], 128, 128),
                'telephone' =>  $result['telephone'],
                'email'     =>  $result['email'],
                'website'   =>  '', //$result['website'],
                'city'      =>  $result['city'],
                'country'   =>  $result['country'],
                'zipcode'   =>  $result['zipcode'],
                'state'     =>  $result['state'],
                'hours'     =>  $result['hours'],         
                'hours1'    =>  empty($result['hours1'])?'':$result['hours1'],
                'hours2'    =>  empty($result['hours2'])?'':$result['hours2'],
                'hours3'    =>  empty($result['hours3'])?'':$result['hours3'],
                'hours4'    =>  empty($result['hours4'])?'':$result['hours4'],
                'hours5'    =>  empty($result['hours5'])?'':$result['hours5'],
                'hours6'    =>  empty($result['hours6'])?'':$result['hours6'],
                'linkedin'  =>  empty($result['linkedin'])?'':$result['linkedin'],
                'facebook'  =>  empty($result['facebook'])?'':$result['facebook'],
                'twitter'   =>  empty($result['twitter'])?'':$result['twitter'],
                'youtube'   =>  empty($result['youtube'])?'':$result['youtube'],
                'vimeo'     =>  empty($result['vimeo'])?'':$result['vimeo'],
                'tag'       =>  $result['tag'],
                'category'  =>  $result['category'],
                'href'      =>  $store_url,
                'color'     =>  trim($result['color']),
                'fontClass' =>  trim($result['fontClass'])
                );
     }
     echo json_encode($_jsonLocationData);
     exit();
 }

}