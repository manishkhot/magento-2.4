<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lof\StoreLocator\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Area;

class Importlocation
{
    const XML_PATH_ENABLE_CRON = 'scheduled_import_settings/enable_cron_tab';
    const EXP_LOG = 'lofstorelocator_exception.log';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Resource
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $_processor;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    protected $_dataHelper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;

    /**
    * @var \Magento\Framework\UrlInterface
    */
    protected $urlBuilder;

     /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filesystem;

    /**
    * CSV Processor
    *
    * @var \Magento\Framework\File\Csv
    */
    protected $csvProcessor;

    /**
    * CSV Processor
    *
    * @var \Magento\Framework\File\Csv
    */
    protected $storeCategory;

     /**
    * CSV Processor
    *
    * @var \Magento\Framework\File\Csv
    */
    protected $storeTag;

     /**
    * CSV Processor
    *
    * @var \Magento\Framework\File\Csv
    */
    protected $storeLocator;

    protected $_data = [];

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $processor
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Lof\StoreLocator\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $processor,
        \Magento\Framework\View\LayoutInterface $layout,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Filesystem $filesystem,
        \Lof\StoreLocator\Helper\Data $dataHelper,
        \Magento\Framework\File\Csv $csvProcessor,
        \Lof\StoreLocator\Model\Category $storeCategory,
        \Lof\StoreLocator\Model\Tag $storeTag,
        \Lof\StoreLocator\Model\StoreLocator $storeLocator
    ) {
        $this->_storeManager = $storeManager;
        $this->_resource = $resource;
        $this->_dateTime = $dateTime;
        $this->_localeDate = $localeDate;
        $this->_eavConfig = $eavConfig;
        $this->_processor = $processor;
        $this->_dataHelper = $dataHelper;
        $this->_layout = $layout;
        $this->logger = $logger;
        $this->inlineTranslation    = $inlineTranslation;
        $this->scopeConfig          = $scopeConfig;
        $this->urlBuilder           = $urlBuilder;
        $this->_filesystem = $filesystem;
        $this->csvProcessor = $csvProcessor;
        $this->storeCategory = $storeCategory;
        $this->storeTag = $storeTag;
        $this->storeLocator = $storeLocator;
    }

    /**
     * Retrieve write connection instance
     *
     * @return bool|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function _getConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resource->getConnection();
        }
        return $this->_connection;
    }

    public function getRootDirPath( $path_type = "") {
        $path_type = $path_type?$path_type:DirectoryList::ROOT;
        return $this->_filesystem->getDirectoryRead($path_type)->getAbsolutePath();
    }

    /**
     * Add products to changes list with price which depends on date
     *
     * @return void
     */
    public function execute()
    {
        if(!$this->_dataHelper->getConfig(self::XML_PATH_ENABLE_CRON))
            return false;

        $list_files = $this->_dataHelper->getImportFiles();
        if($list_files) {
            try{
                $locator = array();
                foreach($list_files as $filename => $filepath) {
                    //Read CSV file content
                    if (!file_exists($filepath)) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('Invalid import csv file.'));
                    }
                    $importLocatorRawData = $this->csvProcessor->getData($filepath);
                    if($importLocatorRawData) {
                        //Get heading field
                        $heading_array = $importLocatorRawData[0];
                        unset($importLocatorRawData[0]);

                        $tag_field_index = array();
                        $category_field_index = array();

                        if($heading_array) {
                            foreach($heading_array as $key=>$val) {
                                if(strpos($val, "tag_") !== false){
                                    $val = str_replace("tag_", "", $val);
                                    $tag_field_index[$key] = $val;
                                    unset($heading_array[$key]);
                                } else if(strpos($val, "category_") !== false) {
                                    $val = str_replace("category_", "", $val);
                                    $category_field_index[$key] = $val;
                                    unset($heading_array[$key]);
                                }
                            }
                        }

                        foreach ($importLocatorRawData as $rowIndex => $dataRow) {
                            $tag_data = array();
                            $category_data = array();
                            $locator_data = array();

                            //Import tag list
                            if($tag_field_index) {
                                foreach($tag_field_index as $tag_key => $tag_field){
                                    if(isset($dataRow[$tag_key]) && $dataRow[$tag_key]) {
                                        $tag_data[$tag_field] = $dataRow[$tag_key];
                                        unset($dataRow[$tag_key]);
                                    }
                                }
                                if($tag_data && isset($tag_data['name']) && $tag_data['name']){
                                    $this->storeTag->importData($tag_data);
                                }
                            }
                            //Import category list
                            if($category_field_index) {
                                foreach($category_field_index as $cat_key => $cat_field){
                                    if(isset($dataRow[$cat_key]) && $dataRow[$cat_key]) {
                                        $category_data[$cat_field] = $dataRow[$cat_key];
                                    }
                                }
                                if($category_data && isset($category_data['name']) && $category_data['name']){
                                    $this->storeCategory->importData($category_data);
                                }
                            }

                            //Import store locator information
                            if($heading_array) {
                                foreach($heading_array as $h_key => $h_field){
                                    if(isset($dataRow[$h_key]) && $dataRow[$h_key]) {
                                        $locator_data[$h_field] = $dataRow[$h_key];
                                    }
                                }
                                if(isset($locator_data['address']) && $locator_data['address']){
                                    if((!isset($locator_data['lat']) || !isset($locator_data['lng'])) || (!isset($locator_data['lat']) && !$locator_data['lat']) || (!isset($locator_data['lng']) && !$locator_data['lng']) ){
                                        $lat_lng = $this->_dataHelper->getLatLngByAddress($locator_data['address']);

                                        if($lat_lng) {
                                            $locator_data['lat'] = $lat_lng['lat'];
                                            $locator_data['lng'] = $lat_lng['lng'];
                                        }
                                    }
                                }
                                if(!isset($locator_data['fontClass']) || (isset($locator_data['fontClass']) && !$locator_data['fontClass'])) {
                                    $locator_data['fontClass'] = 'fa-apple';
                                }
                                if(!isset($locator_data['color']) || (isset($locator_data['color']) && !$locator_data['color'])) {
                                    $locator_data['color'] = '#e67f1e';
                                }
                                //
                            }

                            if($locator_data) {
                                if($tag_data && isset($tag_data['name'])) {
                                    $locator_data['tag'] = $tag_data['name'];
                                }
                                if($category_data && isset($category_data['name'])) {
                                    $locator_data['category'] = $category_data['name'];
                                }

                                if($locator_data && isset($locator_data['name']) && $locator_data['name']){
                                    $this->storeLocator->importData($locator_data);
                                }
                            }
                        }
                        $file_name = basename($filepath);
                        $this->_dataHelper->moveFile($filepath, $file_name, "storelocator_imported");
                    }
                }

            } catch (\Exception $e) {
               \Zend_Debug::dump($e);
            }
        }
        
        return true;
    }

    public function checkExitsFolder($path = ""){
        if (!is_dir($path)) {
            mkdir($path,0755,true);
        }
        return $path;
    }

    
}
