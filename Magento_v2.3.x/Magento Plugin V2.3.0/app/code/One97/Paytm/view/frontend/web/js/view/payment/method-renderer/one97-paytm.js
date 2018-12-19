define(
    [
    'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, placeOrderAction, selectPaymentMethodAction, customer, checkoutData, additionalValidators) {
        'use strict';
        $('.errP').text('');
        var promo='';
        /*alert(window.checkoutConfig.payment.paytm.hide_promo_field);
        alert($.trim(window.checkoutConfig.payment.paytm.promoCode));
        alert($.trim(window.checkoutConfig.payment.paytm.promoCode).length);
        alert(window.checkoutConfig.payment.paytm.promoCode);*/
        if(window.checkoutConfig.payment.paytm.hide_promo_field=='1'){
            if(window.checkoutConfig.payment.paytm.promo_code_local_validation=='1' && ($.trim(window.checkoutConfig.payment.paytm.promoCode).length=='0' || $.trim(window.checkoutConfig.payment.paytm.promoCode)=='')){
                // $('.customDiv').css('display','block');
            }else{
                setTimeout(function(){
                    $('.customDiv').css('display','block');
                }, 1000);
            }

        }
        $(document).on('click','.promoApplyBtn',function(){
            // alert(window.checkoutConfig.payment.paytm.promoCode);
            if(window.checkoutConfig.payment.paytm.promo_code_local_validation=='1'){
                if($.trim($('.promoCodeField').val())==''){
                    $('.errP').text('Please enter your promo code.');
                }else{
                    var promoCode=window.checkoutConfig.payment.paytm.promoCode;
                    var promoCodeArr=promoCode.split(',');
                    var matchCode=0;
                    $.each(promoCodeArr, function( index, value ) {
                        if($.trim($('.promoCodeField').val())==$.trim(value )){
                            matchCode=1;
                        }
                    });
                    if(matchCode==1){
                        $('.promoApplyBtn').val('Remove Code');
                        $('.promoApplyBtn').addClass('promoRemoveBtn');
                        $('.promoApplyBtn').removeClass('promoApplyBtn');
                        $('.promoRemoveBtn').css('background','red');
                        $('.promoRemoveBtn').css('border','1px solid red');
                        $('.promoCodeField').attr('disabled',true);
                        $('.errP').text('Applied Successfully');
                        promo=$.trim($('.promoCodeField').val());
                    }else{
                        $('.errP').text('Incorrect Promo Code');
                        promo='';
                    }
                }
            }else{
                $('.promoApplyBtn').val('Remove Code');
                $('.promoApplyBtn').addClass('promoRemoveBtn');
                $('.promoApplyBtn').removeClass('promoApplyBtn');
                $('.promoRemoveBtn').css('background','red');
                $('.promoRemoveBtn').css('border','1px solid red');
                $('.promoCodeField').attr('disabled',true);
                $('.errP').text('Applied Successfully');
                promo=$.trim($('.promoCodeField').val());
            }
        });
        $(document).on('click','.promoRemoveBtn',function(){
            $('.promoRemoveBtn').val('Apply');
            $('.promoRemoveBtn').addClass('promoApplyBtn');
            $('.promoRemoveBtn').removeClass('promoRemoveBtn');
            $('.promoApplyBtn').css('background','#006bb4');
            $('.promoApplyBtn').css('border','1px solid #006bb4');
            $('.promoCodeField').attr('disabled',false);
            $('.promoCodeField').val('');
            $('.errP').text('');
            promo='';
        });
        return Component.extend({
            defaults: {
                template: 'One97_Paytm/payment/one97'
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },
            afterPlaceOrder: function () {
                if(promo==''){
                    $.mage.redirect(window.checkoutConfig.payment.paytm.redirectUrl);
                }else{
                    $.mage.redirect(window.checkoutConfig.payment.paytm.redirectUrl+"?promo="+promo);
                }
            }
        });
    }
);