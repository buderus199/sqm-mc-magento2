<?php
/**
 * SqmMc Magento Component
 *
 * @category SqualoMail
 * @package SqmMcMagentoTwo
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/9/17 5:20 PM
 * @file: SaveAfter.php
 */
namespace SqualoMail\SqmMcMagentoTwo\Observer\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \SqualoMail\SqmMcMagentoTwo\Model\SqmMcSyncEcommerce
     */
    protected $_ecommerce;
    /**
     * @var \SqualoMail\SqmMcMagentoTwo\Helper\Data
     */
    protected $_helper;
    /**
     * @var \SqualoMail\SqmMcMagentoTwo\Model\Api\Subscriber
     */
    protected $_subscriberApi;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \SqualoMail\SqmMcMagentoTwo\Model\SqmMcSyncEcommerce $ecommerce
     * @param \SqualoMail\SqmMcMagentoTwo\Helper\Data $helper
     * @param \SqualoMail\SqmMcMagentoTwo\Model\Api\Subscriber $subscriberApi
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \SqualoMail\SqmMcMagentoTwo\Model\SqmMcSyncEcommerce $ecommerce,
        \SqualoMail\SqmMcMagentoTwo\Helper\Data $helper,
        \SqualoMail\SqmMcMagentoTwo\Model\Api\Subscriber $subscriberApi,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager
    ) {

        $this->_ecommerce           = $ecommerce;
        $this->_helper              = $helper;
        $this->_subscriberApi       = $subscriberApi;
        $this->_subscriberFactory   = $subscriberFactory;
        $this->_storeManager        = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         */
        $factory = $this->_subscriberFactory->create();
        $subscriber = $observer->getSubscriber();
        $isCustomer = $subscriber->getCustomerId();
        $websiteId = $this->_storeManager->getStore($subscriber->getStoreId())->getWebsiteId();
        if ($isCustomer) {
            $subscriberOld = $factory->loadBySubscriberEmail($subscriber->getSubscriberEmail(), $websiteId);
        }
        if ($this->_helper->isSqmMcEnabled($subscriber->getStoreId())&&$isCustomer&&
            $subscriberOld->getEmail()&&$subscriber->getEmail()!=$subscriberOld->getEmail()) {
            $api = $this->_helper->getApi($subscriberOld->getStoreId());
            $mergeVars = $this->_helper->getMergeVarsBySubscriber($subscriberOld, $subscriberOld->getEmail());
            $status = 'unsubscribed';
            try {
                $md5HashEmail = hash('md5', strtolower($subscriberOld->getEmail()));
                $return = $api->lists->members->addOrUpdate(
                    $this->_helper->getDefaultList($subscriberOld->getStoreId()),
                    $md5HashEmail,
                    null,
                    $status,
                    $mergeVars,
                    null,
                    null,
                    null,
                    null,
                    $subscriberOld->getEmail(),
                    $status
                );
            } catch (\SqualoMailMc_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            }

        }
        $this->_subscriberApi->update($subscriber);
    }
}
