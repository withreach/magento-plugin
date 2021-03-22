require([
    'jquery',
    'domReady!'],
    function ($) {

        function setDhlPickupAccountAttributes(sandboxMode) {

            try {

                // catch any pre-knockout rendering noise
                if (isNaN(sandboxMode)) {
                    return;
                }

                if (sandboxMode === 1) {
                    jQuery("#row_payment_us_reach_payment_reach_dhl_dhl_pickup_account").hide();
                }
                else {
                    jQuery("#row_payment_us_reach_payment_reach_dhl_dhl_pickup_account").show();
                }

            } catch (e) {
                console.log(e.message);
            }
        }

        // register onChange for sandbox mode control
        jQuery('#payment_us_reach_payment_mode').change(function () {
            let sandboxMode = parseInt(jQuery('#payment_us_reach_payment_mode').val(), 10);
            setDhlPickupAccountAttributes(sandboxMode);
        });

        // prime the control visibility
        let sandboxMode = parseInt(jQuery('#payment_us_reach_payment_mode').val(), 10);
        setDhlPickupAccountAttributes(sandboxMode);
    }
);
