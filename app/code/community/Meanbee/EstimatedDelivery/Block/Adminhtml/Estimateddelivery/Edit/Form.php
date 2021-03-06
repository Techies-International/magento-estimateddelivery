<?php
class Meanbee_EstimatedDelivery_Block_Adminhtml_Estimateddelivery_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    const ZERO_DATETIME_INTERVAL = 'P0Y0M0DT0H0M0S'; // @see en.wikipedia.org/wiki/ISO_8601#Time_intervals

    protected function _prepareForm() {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('meanbee_estimateddelivery/estimateddelivery')->load($id)->addShippingMethods();

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $id)),
            'method' => 'post',
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('meanbee_estimateddelivery')->__('Estimated Delivery Configuration'),
            'class'     => 'fieldset-wide',
        ));

        $fieldset->addField('shipping_methods', 'multiselect', array(
            'label'    => 'Shipping Methods',
            'title'    => 'Shipping Methods',
            'name'     => 'shipping_methods',
            'values'    => $this->_getShippingMethods(),
            'required'  => true
        ));

        $fieldset->addField('dispatch_preparation', 'text', array(
            'label'    => 'Dispatch Preparation Time (Days)',
            'title'    => 'Dispatch Preparation Time (Days)',
            'name'     => 'dispatch_preparation',
            'class'     => 'validate-non-negative-number validate-digits',
            'required'  => true
        ));

        $fieldset->addField('dispatch_time_holidays', 'select', array(
            'label'    => 'Preparation Time excludes Holidays of',
            'title'    => 'Preparation Time excludes Holidays of',
            'name'     => 'dispatch_time_holidays',
            'values'   => Mage::getModel('meanbee_estimateddelivery/source_holidayRegions')->toOptionArray()
        ));

        $fieldset->addField('dispatchable_days', 'multiselect', array(
            'label'    => 'Dispatchable Days',
            'title'    => 'Dispatchable Days',
            'name'     => 'dispatchable_days',
            'values'   =>  Mage::getModel('adminhtml/system_config_source_locale_weekdays')->toOptionArray(),
            'required'  => true
        ));

        $fieldset->addField('dispatch_day_holidays', 'select', array(
            'label'    => 'Dispatchable Days excludes Holidays of',
            'title'    => 'Dispatchable Days excludes Holidays of',
            'name'     => 'dispatch_day_holidays',
            'values'   => Mage::getModel('meanbee_estimateddelivery/source_holidayRegions')->toOptionArray()
        ));

        $fieldset->addField('last_dispatch_time', 'select', array(
            'label'    => 'Latest Dispatch Time',
            'title'    => 'Latest Dispatch Time',
            'name'     => 'last_dispatch_time',
            'values'   => Mage::getModel('meanbee_estimateddelivery/system_config_source_times')->toOptionArray(),
            'required'  => true
        ));

        $fieldset->addField('estimated_delivery_from', 'text', array(
            'label'    => 'Estimated Delivery Days (Lower Bound)',
            'title'    => 'Estimated Delivery Days (Lower Bound)',
            'name'     => 'estimated_delivery_from',
            'class'     => 'validate-non-negative-number validate-digits',
            'required'  => true
        ));

        $fieldset->addField('estimated_delivery_to', 'text', array(
            'label'    => 'Estimated Delivery Days (Upper Bound)',
            'title'    => 'Estimated Delivery Days (Upper Bound)',
            'name'     => 'estimated_delivery_to',
            'class'     => 'validate-non-negative-number validate-digits',
            'required'  => true
        ));

        $fieldset->addField('delivery_time_holidays', 'select', array(
            'label'    => 'Transit Time excludes Holidays of',
            'title'    => 'Transit Time excludes Holidays of',
            'name'     => 'delivery_time_holidays',
            'values'   => Mage::getModel('meanbee_estimateddelivery/source_holidayRegions')->toOptionArray()
        ));

        $fieldset->addField('deliverable_days', 'multiselect', array(
            'label'    => 'Deliverable Days',
            'title'    => 'Deliverable Days',
            'name'     => 'deliverable_days',
            'values'   =>  Mage::getModel('adminhtml/system_config_source_locale_weekdays')->toOptionArray(),
            'required'  => true
        ));

        $fieldset->addField('delivery_day_holidays', 'select', array(
            'label'    => 'Deliverable Days excludes Holidays of',
            'title'    => 'Deliverable Days excludes Holidays of',
            'name'     => 'delivery_day_holidays',
            'values'   => Mage::getModel('meanbee_estimateddelivery/source_holidayRegions')->toOptionArray()
        ));

        $fieldset->addField('select_slot_resolution', 'select', array(
            'label'    => 'Resolution of delivery slot selection',
            'title'    => 'Resolution of delivery slot selection',
            'name'     => 'select_slot_resolution',
            'values'   => Mage::getModel('meanbee_estimateddelivery/source_timeResolution')->toOptionArray()
        ));

        $fieldset->addType('dateinterval', Mage::getConfig()->getBlockClassName('meanbee_estimateddelivery/form_element_dateInterval'));
        $fieldset->addField('select_slot_upper_limit', 'dateinterval', array(
            'label'    => 'Upper limit of delivery slot selection',
            'title'    => 'Upper limit of delivery slot selection',
            'name'     => 'select_slot_upper_limit',
            'value'    => self::ZERO_DATETIME_INTERVAL
        ));

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _getShippingMethods() {
        $values = array();

        try {
            $options = Mage::getModel('adminhtml/system_config_source_shipping_allmethods')->toOptionArray();

            // Patch for matrix rates not supporting getAllowedMethods properly
            if (Mage::helper('core')->isModuleEnabled('Webshopapps_Matrixrate')) {
                $options = array_merge($options, $this->_getMatrixRatesMethods());
            }

        } catch (Exception $e) {
            return array(
                array('value'=>0,'label'=>'Unable to retreive shipping methods.'),
                array('value'=>1,'label'=>'Try going to System > Configuration'),
                array('value'=>2,'label'=>'Click the Shipping Methods tab'),
                array('value'=>3,'label'=>'then click "Save".')
            );
        }

        foreach ($options as $option) {
            if (!isset($option['value']) || !is_array($option['value'])) continue;

            foreach ($option['value'] as $value) {
                $values []= $value;
            }
        }
        return $values;
    }

    protected function _getMatrixRatesMethods() {
        if (!class_exists(Webshopapps_Matrixrate_Model_Mysql4_Carrier_Matrixrate_Collection)) {
            return array();
        }

        $collection = Mage::getResourceModel('matrixrate_shipping/carrier_matrixrate_collection')->getData();
        $options = array('matrixrate' => array('value' => array()));

        foreach ($collection as $row) {
            $options['matrixrate']['value'][] =
                    array('value' => 'matrixrate_matrixrate_' . $row['pk'],
                            'label' => '[matrixrate] MatrixRate ' . $row['pk']);

        }

        return $options;

    }
}
