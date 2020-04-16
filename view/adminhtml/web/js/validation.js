require([
        'jquery',
        'mage/translate',
        'jquery/validate'],
    function($){
        $.validator.addMethod(
            "reach-default-hs-code-validator", function (hscode) {
                //Got help/hints from
                //https://magento.stackexchange.com/questions/95171/magento-2-form-validation
                //https://magento.stackexchange.com/questions/139023/custom-field-validation-in-system-xml
                //https://regex101.com/
                //allows twenty characters (after removing `.` and `-`) in the hscode string.
                // With `.` and/or `-` the string length may be longer than 20 from this routine
                //as of now; however that is getting checked and restricted again by built in max length checker:
                //`validate-length maximum-length-20`.
                //At the end we are good
                //Does not accept two consecutive special characters but allows mix and match of `.` and `-` in the
                //hscode string
                //check is done only at frontend level
                //additionally could be done/enforced when getting saved in db (in a path way bypassing UI)

                return (/^[\d]{2}([\.\-]?[A-Za-z0-9]){0,18}$/.test(hscode));
            }, $.mage.__('Please enter a string that starts with two digits and have at least 2 characters and at most 20 characters (letter and numbers)'));
    }
);