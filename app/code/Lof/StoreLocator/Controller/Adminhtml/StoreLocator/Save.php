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
namespace Lof\StoreLocator\Controller\Adminhtml\StoreLocator;

use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
{
    
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $jsHelper;

    /**
     * stdlib timezone.
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_stdTimezone;

    /**
     * @param \Magento\Backend\App\Action\Context
     * @param \Magento\Framework\ObjectManagerInterface
     * @param \Magento\Framework\Filesystem
     * @param \Magento\Backend\Helper\Js
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context, 
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Stdlib\DateTime\Timezone $_stdTimezone
        ) {
        $this->_fileSystem = $filesystem;
        $this->jsHelper = $jsHelper;
        $this->_stdTimezone = $_stdTimezone;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Lof_StoreLocator::storelocator_save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue(); 

        $dateTimeNow = $this->_stdTimezone->date()->format('Y-m-d H:i:s');
        $links = $this->getRequest()->getPost('links');
        $links = is_array($links) ? $links : [];

        if(!empty($links)){
            // use position is value for array id
            // if (!empty($links['categories'])) {
            //     $categories = $this->jsHelper->decodeGridSerializedInput($links['categories']);
            //     $data['category'] = $categories;
            // }
            // if (!empty($links['tags'])) {
            //     $tags = $this->jsHelper->decodeGridSerializedInput($links['tags']);
            //     $data['tag'] = $tags;
            // }
        }
        // Categories checkboxes
        if( isset($data['selectedCategories']) ) {

            if (is_array($data['selectedCategories'])){
                $ids = implode(",", $data['selectedCategories']);
                $modelCategory = $this->_objectManager->create('Lof\StoreLocator\Model\Category');
                $data['category'] = $modelCategory->getListCategoryNameByIds($ids);
            } else {
                unset($data['selectedCategories']);
            }
        }

        // tags checkboxes
        if( isset($data['selectedTags']) ) {
            
            if (is_array($data['selectedTags'])){
                $ids = implode(",", $data['selectedTags']);
                $modelTag = $this->_objectManager->create('Lof\StoreLocator\Model\Tag');
                $data['tag'] = $modelTag->getListTagNameByIds($ids);
            } else {
                unset($data['selectedTags']);
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->_objectManager->create('Lof\StoreLocator\Model\StoreLocator');

            $id = $this->getRequest()->getParam('storelocator_id');
            if ($id) {
                $model->load($id);
            }

            /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
            ->getDirectoryRead(DirectoryList::MEDIA);
            $mediaFolder = 'lof/storelocator/';
            $path = $mediaDirectory->getAbsolutePath($mediaFolder);

            // Delete, Upload Image
            $imagePath = $mediaDirectory->getAbsolutePath($model->getImage());
            if(isset($data['image']['delete']) && file_exists($imagePath)){
                unlink($imagePath);
                $data['image'] = '';
            }
            if(isset($data['image']) && is_array($data['image'])){
                unset($data['image']);
            }
            if($image = $this->uploadImage('image')){
                
                $data['image'] = $image;
            }
            // Delete, Upload Maker Icon Image
            $makerIconPath = $mediaDirectory->getAbsolutePath($model->getMakerIcon());
            if(isset($data['maker_icon']['delete']) && file_exists($makerIconPath)){
                unlink($makerIconPath);
                $data['maker_icon'] = '';
            }
            if(isset($data['maker_icon']) && is_array($data['maker_icon'])){
                unset($data['maker_icon']);
            }
            if($maker_icon = $this->uploadImage('maker_icon')){
                
                $data['maker_icon'] = $maker_icon;
            }
            $data['create_time'] = $dateTimeNow;
            
            // upload marker icon 
       
            $model->setData($data);
            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved this StoreLocator.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['storelocator_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the storelocator.'));
                $this->messageManager->addError($e->getMessage());
            }
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['storelocator_id' => $this->getRequest()->getParam('storelocator_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    public function uploadImage($fieldId = 'image')
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if (isset($_FILES[$fieldId]) && $_FILES[$fieldId]['name']!='') 
        {
            $uploader = $this->_objectManager->create(
                'Magento\Framework\File\Uploader',
                array('fileId' => $fieldId)
                );

            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
            ->getDirectoryRead(DirectoryList::MEDIA);
            $mediaFolder = 'lof/storelocator/';
            try {
                $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png')); 
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);
                $result = $uploader->save($mediaDirectory->getAbsolutePath($mediaFolder)
                    );
                return $mediaFolder.$result['name'];
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['storelocator_id' => $this->getRequest()->getParam('storelocator_id')]);
            }
        }
        return;
    }

}