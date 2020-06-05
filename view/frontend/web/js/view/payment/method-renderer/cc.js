define([
        'jquery',     
        'ko',   
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/translate'
    ],
    function (
        $, 
        ko,
        Component,
        customer,
        additionalValidators, 
        setPaymentInformationAction, 
        fullScreenLoader,
        placeOrderAction,
        redirectOnSuccessAction,
        $t
        ) {
        'use strict';
        
        var isLoggedIn = ko.observable(window.isCustomerLoggedIn);
        var contractOptions=[];
        if (isLoggedIn()) {
            if (typeof window.checkoutConfig.payment.reach_cc.open_contracts !== 'undefined' &&
                    window.checkoutConfig.payment.reach_cc.open_contracts.length)
            {
                var contracts = window.checkoutConfig.payment.reach_cc.open_contracts;
                $.each(contracts, function (key, item) {
                    contractOptions.push(item);
                });
                contractOptions.push({contractId:0,label:$t('Use Different Card')});
            }  
            console.log(contractOptions);            
        }
        return Component.extend({
            defaults: {
                template: 'Reach_Payment/payment/cc',
                cardHolderName:'',
                stashId:null,
                opencontract:false,
                selectedContract:null,
                iscCFormVisible:!customer.isLoggedIn() || contractOptions.length === 0
            },
            contractOptions:contractOptions,
            stashHandler:null,            
            placeOrderHandler: null,
            validateHandler: null,
              /** @inheritdoc */
            initObservable: function () {
                this._super()
                    .observe([
                        'cardHolderName',
                        'stashId',
                        'opencontract',
                        'selectedContract',
                        'iscCFormVisible'
                    ]);

                return this;
            },
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();   
                this.getDeviceFingerPrint();
                return this;
            },
            getDeviceFingerPrint:function (){
                var s = document.createElement("script");
                s.type = "text/javascript";
                s.src = this.getFingerprinturl();
                $("head").append(s);
            },
            getCcAvailableTypes: function () {
                //got idea from here: https://webkul.com/blog/adding-additional-variables-in-window-checkoutconfig-on-magento-2-checkout-page/
                //return window.checkoutConfig.payment.ccform.availableTypes[this.getCode()];
                console.log(window.checkoutConfig.payment.reach_cc.availableTypes);
                return window.checkoutConfig.payment.reach_cc.availableTypes;
            },
            getIcons: function (type) {
                //console.log(window.checkoutConfig.payment.reach_cc.icons);
                console.log(type);
                console.log(window.checkoutConfig.payment.reach_cc.icons[type]);
                //return window.checkoutConfig.payment.reach_cc.icons;
                //return window.checkoutConfig.payment.ccform.icons;
                return window.checkoutConfig.payment.reach_cc.icons.hasOwnProperty(type) ?
                    window.checkoutConfig.payment.reach_cc.icons[type]
                    : false;
            },
            getFingerprinturl:function(){
               return window.checkoutConfig.reach.fingerprint_url;
            },
            /**
             * @param {Function} handler
             */
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            /**
             * @param {Function} handler
             */

            setStashHandler: function (handler) {
                this.stashHandler = handler;
            },
            /**
             * @param {Function} handler
             */
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },          
            contractOptionsText: function (contract) {                
                return contract.label;
            },
            onContractChange: function (contract){                
                if(contract.contractId==0)
                {
                    this.iscCFormVisible(true);
                }
                else{
                    this.iscCFormVisible(false);
                }                
            },

            /**
             * @returns {Object}
             */
            context: function () {
                return this;
            },

            /**
             * @returns {Boolean}
             */
            isShowLegend: function () {
                return true;
            },

            /**
             * @returns {String}
             */
            getCode: function () {
                return 'reach_cc';
            },
            
            /**
             * @returns {Boolean}
             */
            isActive: function () {
                return true;
            },

            /**
             * @override
             */
            placeOrder: function () {
                var self = this;                
                
                var selectedContracts = this.selectedContract();
                if(selectedContracts && selectedContracts.contractId)
                {
                    setPaymentInformationAction(this.messageContainer,this.getData());
                    this._saveOrder();
                }
                else{
                    if (this.validateHandler() && additionalValidators.validate()) {
                        this.isPlaceOrderActionAllowed(false);
                        fullScreenLoader.startLoader();
                        self.stashHandler();
                    }
                }
                
            },

            /**
             * @returns {Object}
             */
            getData: function () {
                var selectedContracts = this.selectedContract();
                if(selectedContracts && selectedContracts.contractId)
                {
                    var data = {
                        'method': this.getCode(),
                        'additional_data': {
                        'contract_id':selectedContracts.contractId,
                        'device_fingerprint':gip_device_fingerprint
                        }
                    };                    
                    return data;
                }

                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'stash_id':this.stashId(),
                        'cc_owner': this.cardHolderName(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_last_4': this.creditCardNumber().substr(-4),
                        'oc_selected':this.opencontract(),
                        'device_fingerprint':gip_device_fingerprint
                    }
                };
                return data;
            },

            saveOrder: function (stash_id){
                this.stashId(stash_id);                
                setPaymentInformationAction(this.messageContainer,this.getData());
                this._saveOrder();
            },
            _saveOrder: function(){
               var self = this;

               this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();
               $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                )
               .fail(
                    function () {
                        fullScreenLoader.stopLoader();
                        self.isPlaceOrderActionAllowed(true);
                    }
                ).done(
                    function () {                            
                        fullScreenLoader.stopLoader();
                        redirectOnSuccessAction.execute();                            
                    }
                );
            },
              /**
             * @returns {Boolean}
             */
            isOcEnabled: function () {
                if(!customer.isLoggedIn())
                {
                    return false;
                }
                return typeof window.checkoutConfig.payment.reach_cc.oc_enabled !== 'undefined' &&
                    window.checkoutConfig.payment.reach_cc.oc_enabled === true;
            }
        });
    }
);
