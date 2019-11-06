/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
	'jquery',
    'Magento_Checkout/js/view/payment/default',
    'mage/storage',
    'mage/url',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators'
], function ($, Component, storage, url, urlBuilder, customer, quote, fullScreenLoader, additionalValidators) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Reach_Payment/payment/paypal'
        },
         initialize: function () {
            this._super();   
            this.getDeviceFingerPrint();
            return this;
        },
        getCode: function () {
            return 'reach_paypal';
        },

        getDeviceFingerPrint:function (){
            var s = document.createElement("script");
            s.type = "text/javascript";
            s.src = this.getFingerprinturl();
            $("head").append(s);
        },

        getFingerprinturl:function(){
            return window.checkoutConfig.reach.fingerprint_url;
        },
        /** Returns payment information data */
        getData: function () {
            return $.extend(true, this._super(), {'additional_data': null});
        },
        placeOrder: function () {

            var self = this;
            self.resetErrors();
            if (!this.validate() || !additionalValidators.validate()) {
                return false;
            }

            fullScreenLoader.startLoader();

            /**
             * Save billing address
             * Checkout for guest and registered customer.
             */
            var serviceUrl,
                payload;
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/billing-address', {
                    cartId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    address: quote.billingAddress()
                };
                payload.address.email = quote.guestEmail;

            } else {

                serviceUrl = urlBuilder.createUrl('/carts/mine/billing-address', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    address: quote.billingAddress()
                };
            }

			return storage.post(
		        serviceUrl,
		        JSON.stringify(payload)
		    ).done(
		        function () {
		        	var paymentData = {method: self.getCode()};

		        	 /**
                         * Set payment method
                         * Checkout for guest and registered customer.
                         */
                        if (!customer.isLoggedIn()) {
                            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/selected-payment-method', {
                                cartId: quote.getQuoteId()
                            });
                            payload = {
                                cartId: quote.getQuoteId(),
                                method: paymentData
                            };
                        } else {
                            serviceUrl = urlBuilder.createUrl('/carts/mine/selected-payment-method', {});
                            payload = {
                                cartId: quote.getQuoteId(),
                                method: paymentData
                            };
                        }

                        return storage.put(
                            serviceUrl,
                            JSON.stringify(payload)
                        ).done(
                            function () {

                            	var paypalUrl = null;
                                if (customer.isLoggedIn()) {
                                    paypalUrl = urlBuilder.createUrl(
                                        '/reach/paypal/:cartId/:deviceFingerprint',
                                        {cartId: quote.getQuoteId(),deviceFingerprint:gip_device_fingerprint}
                                    );
                                } else {
                                    paypalUrl = urlBuilder.createUrl(
                                        '/reach/paypal-guest/:cartId/:deviceFingerprint',
                                        {cartId: quote.getQuoteId(),deviceFingerprint:gip_device_fingerprint}
                                    );
                                }
                                 
                                storage.get(paypalUrl)
                                    .done(
                                        function (response) {                                        	
                                            if (response.success) {
                                            	console.log(response.response[0]);                                            	
                                            	if (response.response[0]) {
                                                    window.location.href = response.response[0];
                                                } else {
                                                    self.displayError("Invalid response from PayPal, please try again later.");
                                                }
                                            } else {
                                                self.displayError(response.error_message);
                                            }
                                       	}	
                                    ).fail(
                                        function (response) {
                                            self.displayError("Unable to submit form to PayPal.");
                                        }
                                    );
                            }
                        ).fail(
                            function (response) {
                                self.displayError("Unable to save payment method.");
                            }
                        );
		        }
		    ).fail(
		        function (response) {
		            self.displayError("Unable to save billing address.");
		        }
		    );

        },
        displayError: function (message) {
            var span = document.getElementById(this.getCode() + '-payment-errors');
            span.innerHTML = message;
            span.style.display = "block";
            fullScreenLoader.stopLoader();
        },
        resetErrors: function () {
            var span = document.getElementById(this.getCode() + '-payment-errors');
            span.style.display = "none";
        }
    });
});
