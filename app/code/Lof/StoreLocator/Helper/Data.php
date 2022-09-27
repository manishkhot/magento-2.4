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
namespace Lof\StoreLocator\Helper;

// chua edit, cai nay de get configuration setting 

use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_ENABLE_CRON = 'scheduled_import_settings/enable_cron_tab';

    const XML_FOLDER_PATH = 'scheduled_import_settings/import_folder';

    protected $file_types = ["csv", "CSV"];
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
    * @var \Magento\Framework\View\Element\BlockFactory
    */
    protected $_blockFactory;
    /** 
    *@var \Magento\Store\Model\StoreManagerInterface 
    */
    protected $_storeManager;

    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    protected $_readFactory;
     /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filesystem;


    CONST GMAP_API_KEY   = 'storelocator/general/api_key';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Filesystem $filesystem,
        ReadFactory $readFactory
        ) {
        parent::__construct($context);
        $this->_localeDate     = $localeDate;
        $this->_scopeConfig    = $context->getScopeConfig();
        $this->_blockFactory   = $blockFactory;
        $this->_storeManager   = $storeManager;
        $this->_filterProvider = $filterProvider;
        $this->_objectManager  = $objectManager;
        $this->_readFactory = $readFactory;
        $this->_filesystem = $filesystem;

    }
    public function getAPIKey(){
        return $this->_scopeConfig->getValue(self::GMAP_API_KEY);
    }

    public function getConfig($key, $store = null)
    {
        $store = $this->_storeManager->getStore($store);
        $websiteId = $store->getWebsiteId();

        $result = $this->scopeConfig->getValue(
            'storelocator/'.$key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store);
        return $result;
    }

    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
        ) {
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : \IntlDateFormatter::NONE,
            null,
            $timezone
            );
    }

    public function getFormatDate($date, $type = 'full'){
        $result = '';
        switch ($type) {
            case 'full':
            $result = $this->formatDate($date, \IntlDateFormatter::FULL);
            break;
            case 'long':
            $result = $this->formatDate($date, \IntlDateFormatter::LONG);
            break;
            case 'medium':
            $result = $this->formatDate($date, \IntlDateFormatter::MEDIUM);
            break;
            case 'short':
            $result = $this->formatDate($date, \IntlDateFormatter::SHORT);
            break;
        }
        return $result;
    }

    public function subString( $text, $length = 100, $replacer ='...', $is_striped=true ){
        if($length == 0) return $text;
        $text = ($is_striped==true)?strip_tags($text):$text;
        if(strlen($text) <= $length){
            return $text;
        }
        $text = substr($text,0,$length);
        $pos_space = strrpos($text,' ');
        return substr($text,0,$pos_space).$replacer;
    }

    public function filter($str)
    {
        $html = $this->_filterProvider->getPageFilter()->filter($str);
        return $html;
    }

    public function getMediaUrl(){
        $storeMediaUrl = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
        ->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $storeMediaUrl;
    }

    public function getRootDirPath( $path_type = "") {
        $path_type = $path_type?$path_type:DirectoryList::PUB;
        return $this->_filesystem->getDirectoryRead($path_type)->getAbsolutePath();
    }

    public function getFileTypes(){
        return $this->file_types;
    }

    /**
     *
     */
    public function getImportFiles() {
        $path = $this->getImportPath();
        $dirs = array_filter(glob($path . '/*'), 'is_dir');
        $filetypes = $this->getFileTypes();
        $output = array();
        foreach($filetypes as $file_type) {
            $tmp = $this->getFileList($path, $file_type);
            $output = array_merge($tmp, $output);
        }
        arsort($output);
        return $output;
    }

    public function getImportPath (){
        $path = $this->getRootDirPath();
        $path = str_replace(array("pub/pub/","pubpub/","pub/"), "", $path);
        $path .= $this->getImportFolderPath();

        return $path;
    }

    public function getImportFolderPath(){
        return $this->getConfig(self::XML_FOLDER_PATH);
    }

    public function getImportedPath() {
        $root_path = $this->_filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();
        return $root_path;
    }

    public function checkExitsFolder($path = ""){
        if (!is_dir($path)) {
            mkdir($path,0755,true);
        }
        return $path;
    }

    public function moveFile($filepath, $filename, $folder = "storelocator_imported") {
        $path = $this->getImportedPath().$folder."/";
        $this->checkExitsFolder($path);

        if(file_exists($filepath)) {
            @copy($filepath, $path.$filename);
            @unlink($filepath);
        }
    }

     /**
     *
     */
    public function getFileList( $path , $e=null, $filter_pattern = "" ) {
        $output = array();
        $directories = glob( $path.'*.'.$e );
        if($directories) {
            foreach( $directories as $dir ){
                if($filter_pattern) { //filter files by pattern
                    $file_name = basename( $dir );
                    if($file_name && strpos($file_name, $filter_pattern) !== false) {
                        $filename = str_replace(".".$e, "", $file_name);
                        $output[$filename] = $path.$file_name;
                    }
                } else { //get direct file
                    $file_name = basename( $dir );
                    if($file_name) {
                        $filename = str_replace(".".$e, "", $file_name);
                        $output[$filename] = $path.$file_name;
                    }
                }
            }
        }
        return $output;
    }
    public function getLatLngByAddress($address = "") {
        $lat_lng = [];
        if($address) {
            $api_key                = $this->getConfig('general/api_key');
            $address = str_replace(" ", "+", $address);
            $json = @file_get_contents("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=".$api_key);
            $json = json_decode($json);
            $lat_lng['lat'] = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
            $lat_lng['lng'] = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
        }
        return $lat_lng;
    }
}