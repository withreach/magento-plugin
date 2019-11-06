
/* @api */
define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'mage/storage',
    'Magento_Checkout/js/model/url-builder',
    'jquery/ui',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, mageTemplate, alert,storage,urlBuilder, ui, validator, fullScreenLoader) {
    'use strict';

    $.widget('reach.stash', {
        options: {
            context: null,
            placeOrderSelector: '[data-role="review-save"]',            
            paymentFormSelector: '#reach-cc-form',
            gateway: null,
        },

        /**
         * {Function}
         * @private
         */
        _create: function () {
            
            this.hiddenFormTmpl = mageTemplate(this.options.hiddenFormTmpl);

            if (this.options.context) {
                this.options.context.setStashHandler($.proxy(this._getServerStash, this));
                this.options.context.setValidateHandler($.proxy(this._validateHandler, this));
            } else {
                $(this.options.placeOrderSelector)
                    .off('click')
                    .on('click', $.proxy(this._getStashHandler, this));
            }

            this.element.validation();
            $('[data-container="' + this.options.gateway + '-cc-number"]').on('focusout', function () {
                $(this).valid();
            });
        },

        /**
         * handler for credit card validation
         * @return {Boolean}
         * @private
         */
        _validateHandler: function () {
            return this.element.validation && this.element.validation('isValid');
        },

        /**
         * handler for Place Order button to call gateway for credit card validation
         * @return {Boolean}
         * @private
         */
        _getStashHandler: function () {            
            if (this._validateHandler()) {
                this._getServerStash();
            }

            return false;
        },

        _getServerStash: function (){

            var stashUrl = urlBuilder.createUrl(
                '/reach/stash', {}          
            );
            var self=this;

                
            fullScreenLoader.startLoader();
            storage.get(stashUrl)
            .done(
                function (response) {    

                    fullScreenLoader.stopLoader();    
                    if (response.success && response.stash) {
                        self._getStash(response.stash);
                    } 
                    else{
                        msg = response['error_messages'];
                        if (typeof msg === 'object') {
                            msg = msg.join('\n');
                        }

                        if (msg) {
                            alert(
                                {
                                    content: msg,
                                }
                            );
                        }
                    }
                }
            ).fail(function(response){
                fullScreenLoader.stopLoader();    
                msg = response['error_messages'];
                if (typeof msg === 'object') {
                    msg = msg.join('\n');
                }

                if (msg) {
                    alert(
                        {
                            content: msg,
                        }
                    );
                }
            });
        },

        /**
         * Save order and generate post data for gateway call
         * @private
         */
        _getStash: function (stash_id) {                    
            var postData ={};
            var url = "https://stash-sandbox.gointerpay.net/"+stash_id;
            postData = {'DeviceFingerprint': gip_device_fingerprint};
            postData.card=JSON.stringify(this.getCardData());
            return $.ajax({
                url: url,
                type: 'post',                                
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                  },
                data:postData,

                /**
                 * {Function}
                 */
                beforeSend: function () {
                    fullScreenLoader.startLoader();
                },

                /**
                 * {Function}
                 */
                success: function (response) { 
                    var stashid, msg;
                    if(typeof response == 'string')
                    {
                        response = JSON.parse(response);
                    }
                    
                    if (response.StashId && response.Warnings.length ==0)
                    {
                        this.options.context.saveOrder(response.StashId);
                    } 
                    else {

                        fullScreenLoader.stopLoader(true);
                        msg = response.Warnings;

                        if (this.options.context) {
                            this.options.context.clearTimeout().fail();                            
                        }

                        msg = msg.join('\n');

                        if (msg) {
                            
                            alert(
                                {
                                    content: msg,
                                 
                                }
                            );
                        }

                    }                    
                    fullScreenLoader.stopLoader(true);
                }.bind(this)
            })
            .always(function () {
                fullScreenLoader.stopLoader(true);
            });
        },
        getCardData: function () {                
            var formData = $(this.options.paymentFormSelector).serializeArray();
            var cardData = {};
            $.map(formData, function(n, i){
                cardData[n['name']] = n['value'];
            });
            var card = {};
            card.Name = cardData['payment[cc_name]'];
            card.Number = cardData['payment[cc_number]'];
            card.Number = cardData['payment[cc_number]'];
            card.VerificationCode = cardData['payment[cc_cid]'];
            card.Expiry = {};
            card.Expiry.Year=cardData['payment[cc_exp_year]'];

            if(parseInt(cardData['payment[cc_exp_month]']) < 10)
            {
                card.Expiry.Month = "0" + cardData['payment[cc_exp_month]'];
            }
            else{
                card.Expiry.Month = cardData['payment[cc_exp_month]'];
            }        
            return card;
        }
    });

    return $.reach.stash;
});
