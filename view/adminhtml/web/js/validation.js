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
                //This routine is enforcing good enough check on length so we possibly can remove the built in
                // validation of the form `validate-length minimum-length-2 maximum-length-20` from system.xml.
                //Does accept two consecutive special characters. Also allows mix and match of `.` and `-` in the
                //hscode string - made it that way after talking (on Slack) to the team.
                //Check is done only at frontend level
                //Additionally the check could be done/enforced when getting saved in db (in a path way bypassing UI)
                //At this point it does not accept white spaces (this is to avoid adding/overriding Admin config model
                // related code - the way we added custom code for our currency model).
                //If it returns false (i.e. the regex validation fails) in that case we show the error text/msg.
                return (/^[\d]{2}[A-Za-z0-9\.-]{0,18}$/.test(hscode));
            }, $.mage.__('Please enter an HS Code that starts with two digits and has at most 20 characters (letters, digits, . and -). White spaces are not allowed.'));
    }
);