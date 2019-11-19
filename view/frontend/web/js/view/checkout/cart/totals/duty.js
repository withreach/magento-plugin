define([
    'ko',
    'Reach_Payment/js/view/checkout/summary/duty',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals'
], function (ko, Component, quote, priceUtils, totals) {
    'use strict';

    var show_hide_duty = window.checkoutConfig.show_hide_duty;
    var duty_title = window.checkoutConfig.duty_title;
    var duty_amount = window.checkoutConfig.duty_amount;

    return Component.extend({
        totals: quote.getTotals(),
        canVisibleCustomFeeBlock: show_hide_duty,        
        getDutyTitle:ko.observable(duty_title),
        getFormattedPrice: function(){            
            return priceUtils.formatPrice(this.getValue(),quote.getPriceFormat());
        },
        isDisplayed: function () {
            return this.getValue() != 0;
        },
        getValue: function() {
            var price = 0;
            if (this.totals() && totals.getSegment('duty')) {
                price = totals.getSegment('duty').value;
            }
            return price;
        }
    });
});