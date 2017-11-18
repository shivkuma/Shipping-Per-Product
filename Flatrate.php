<?php
/**
 * Magento Commercial Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Commercial Edition License
 * that is available at: http://www.magentocommerce.com/license/commercial-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shipping
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/commercial-edition
 */


/**
 * Flat rate shipping model
 *
 * @category   Mage
 * @package    Mage_Shipping
 * @author      Magento Core Team <core@magentocommerce.com>
 */


class Mage_Shipping_Model_Carrier_Flatrate
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
 
    protected $_code = 'flatrate';
    protected $_isFixed = true;
 
    /**
     * Enter description here...
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
 
        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
 
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
 
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeBoxes += $item->getQty() * $child->getQty();
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        $this->setFreeBoxes($freeBoxes);
 
        $result = Mage::getModel('shipping/rate_result');
        if ($this->getConfigData('type') == 'O') { // per order
            $shippingPrice = $this->getConfigData('price');
        } elseif ($this->getConfigData('type') == 'I') { // per item
            $shippingPrice = ($request->getPackageQty() * $this->getConfigData('price')) - ($this->getFreeBoxes() * $this->getConfigData('price'));
        } else {
            $shippingPrice = false;
        }
 
        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);
        $shippingPrice = $this->get_pro_ship();
 
        if ($shippingPrice !== false) {
            $method = Mage::getModel('shipping/rate_result_method');
 
            $method->setCarrier('flatrate');
            $method->setCarrierTitle($this->getConfigData('title'));
 
            $method->setMethod('flatrate');
            $method->setMethodTitle($this->getConfigData('name'));
 
            if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
                $shippingPrice = '0.00';
            }
 
            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);
 
            $result->append($method);
        }
 
        return $result;
    }
 
    public function getAllowedMethods()
    {
        return array('flatrate'=>$this->getConfigData('name'));
    }
 
    public function get_pro_ship()
    {
    Mage::getSingleton('core/session', array('name'=>'frontend'));
    $session = Mage::getSingleton('checkout/session');
    $cart_items = $session->getQuote()->getAllItems();
    $_helper = Mage::helper('catalog/output');
    $custom_ship=0;
    foreach( $cart_items as $items ){
 
        $cur_fproduct = Mage::getModel('catalog/product')->load($items->getProductId());
		if($cur_fproduct->getShipCost()){
			$custom_ship +=($items->getQty())*($_helper->productAttribute($cur_fproduct, $cur_fproduct->getShipCost(), 'ship_cost'));
			//$custom_ship +=($items->getQty())*($cur_fproduct->getShipCost());
		}else{
			$custom_ship +=($items->getQty())*($this->getConfigData('price'));
		}
    }
 
    return $custom_ship ;   
 
    }// function end
 
}
