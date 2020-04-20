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
                //This routine is enforcing good enough check on minimum length so we possibly can remove the built in
                // validation of the form `minimum-length-2` from system.xml.
                //Does accept two or more consecutive special characters. Also allows mix and match of `.` and `-` in the
                //hscode string - made it that way after talking (on Slack) to the team.
                //Check is done only at frontend level
                //If it returns false (i.e. the regex validation fails) in that case we show the error text/msg.
                //It just tackles input validation ; it does not modify or transform the input
                return (/^\s*[\d]{2}[A-Za-z0-9\.\-]{0,18}\s*$/.test(hscode));
            }, $.mage.__('Please enter an HS Code that starts with two digits and has at most 20 characters (letters, digits, . and -).'));
    }
);