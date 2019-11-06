/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
], function (ko,storage,shippingService,urlBuilder,customer,quote,fullScreenLoader) {
    'use strict';

    var duty = ko.observable();
    var dutyOptional = ko.observable();
    return {
        /**
         * @param {Object} address
         */
        getCharges: function (shippingCharge,apply=false) {
        
            var self = this;
            fullScreenLoader.startLoader();
            var address = quote.shippingAddress();

            var payload = JSON.stringify({
                    address: {
                        'street': address.street,
                        'city': address.city,
                        'region_id': address.regionId,
                        'region': address.region,
                        'country_id': address.countryId,
                        'postcode': address.postcode,
                        'email': address.email,
                        'customer_id': address.customerId,
                        'firstname': address.firstname,
                        'lastname': address.lastname,
                        'middlename': address.middlename,
                        'prefix': address.prefix,
                        'suffix': address.suffix,
                        'vat_id': address.vatId,
                        'company': address.company,
                        'telephone': address.telephone,
                        'fax': address.fax,
                        'custom_attributes': address.customAttributes,
                        'save_in_address_book': address.saveInAddressBook
                    },
                    cartId : quote.getQuoteId(),
                    shippingCharge: shippingCharge,
                    shippingMethodCode: quote.shippingMethod()['method_code'],
                    shippingCarrierCode: quote.shippingMethod()['carrier_code'],
                    apply:apply
                }
            );

            var dutyUrl = null;
            if (customer.isLoggedIn()) {
                dutyUrl = urlBuilder.createUrl('/reach/dutycalculation',{});
            } else {
                dutyUrl = urlBuilder.createUrl('/reach/dutycalculation-guest',{});
            }
            storage.post(dutyUrl,payload)
            .done(
                function (response) {    
                    fullScreenLoader.stopLoader();                                       
                    if (response.success) {
                        if(response.duty >= 0)    
                        {
                            duty(response.duty);
                            duty.valueHasMutated();
                            dutyOptional(response.is_optional);
                            dutyOptional.valueHasMutated();
                        }
                    } 
                    else {
                        duty(0);
                        duty.valueHasMutated();
                    }
                }   
            ).fail(
                function (response) {
                    fullScreenLoader.stopLoader();
                }
            );    
        },
        getDuty:function(){
            return duty;
        },
        getDutyIsOptional:function(){
            return dutyOptional;
        }
    }
});
