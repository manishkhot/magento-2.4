<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
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
 * @copyright  Copyright (c) 2017 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */
namespace Lof\StoreLocator\Controller\Adminhtml;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class Testcron extends \Magento\Backend\App\Action
{      
 
    protected $_data = [];
    public function _initAction()
    {
        $this->_view->loadLayout();
        $this->_addBreadcrumb(__('Store Locator'), __('Store Locatorports')); 
        $this->_addBreadcrumb(__('Test Cron Job'), __('Test Cron Job'));
        $this->initVerification();
        return $this;
    }
    public function initVerification() {
        $this->_eventManager->dispatch(
        'lof_check_license',
        ['obj' => $this,'ex'=>'Lof_StoreLocator']
        );
        return $this;
    }

    public function setData($var_name, $var_value = "") {
        $this->_data[$var_name] = $var_value;
        return $this;
    }
    public function getData($var_name) {
        return isset($this->_data[$var_name])?$this->_data[$var_name]:null;
    }
    /**
     * Determine if action is allowed for reports module
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $this->initVerification();
        if (!$this->getData('is_valid') && !$this->getData('local_valid')) {
            return false;
        }
        return $this->_authorization->isAllowed('Lof_StoreLocator::testcron'); 
    }
}
