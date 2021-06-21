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
        var togglepoup = false;
        return Component.extend({
            defaults: {
                template: 'One97_Paytm/payment/one97',
                paytmDataFrameLoaded: false,
                redirectUrl: ''
            },
            isAvailable: function() {
                return this.paytmDataFrameLoaded;
            },
            getMerchantid: function() {
                return window.checkoutConfig.payment.paytm.mid;
            },
            getCheckoutUrl: function() {
                return window.checkoutConfig.payment.paytm.checkout_url;
            },
            getRedirecturl: function() {
                return window.checkoutConfig.payment.paytm.redirecturl;
            },
            //  getordrid: function() {
            //      alert(window.checkoutConfig.payment.paytm.order);
            //      return window.checkoutConfig.payment.paytm.order;
            //  },
            initObservable: function() {
                var self = this._super();              //Resolves UI Error on Checkout


                if(!self.paytmDataFrameLoaded) {
                    $.getScript(self.getCheckoutUrl(), function() {
                        self.paytmDataFrameLoaded = true;
                       
                    });
                }
                return self;
            },
            placeOrder: function (data, event) {
                
                if (event) {
                    event.preventDefault();
                    //$("#paywithpaytm").addClass('paytmtoggle');
                    if ($("#paywithpaytm").hasClass('paytmtoggle')) {
                        togglepoup = true;
                        window.Paytm.CheckoutJS.invoke();
                    }
                }


            if(!togglepoup){

            var loaderhtml = '<div id="paytm-pg-spinner" class="paytm-pg-loader">'+
            '<div class="bounce1"></div>'+
            '<div class="bounce2"></div>'+
            '<div class="bounce3"></div>'+
            '<div class="bounce4"></div>'+
            '<div class="bounce5"></div>'+
            '</div>'+
            '<div class="paytm-overlay paytm-pg-loader"></div>';
             $('body').append(loaderhtml);
             $('.paytm-pg-loader').show();

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
            }
                return false;

            },
            afterPlaceOrder: function () {

                if(!togglepoup){
	
		        var self = this;
                $.ajax({
                    type: 'POST',
                   // url: urlBuilder.build("Standard/Success"),
                    url: window.checkoutConfig.payment.paytm.redirecturl,
                    data: {
                        email: '',
                    },

                    /**
                     * Success callback
                     * @param {Object} response
                     */
                    success: function (response) {

                       var respons = JSON.parse(JSON.stringify(response));
                       if (respons.response.txnToken) {
                            self.renderCheckout(respons.response);
                        } 
                    },
                });
                }


            },
            renderCheckout: function(data) {


            if(!togglepoup){
                var config = {
                    "root": "",
                    "flow": "DEFAULT",
                    "data": {
                            "orderId": data.ORDER_ID,
                            "token": data.txnToken,
                            "tokenType": "TXN_TOKEN",
                            "amount": data.TXN_AMOUNT,
                    },
                    "integration": {
                      "platform": "Magento",
                      "version": data.MAGENTO_VERSION+"|"+data.PLUGIN_VERSION
                    },
                    "handler": {
                        "notifyMerchant": function(eventName,data){
                            if(eventName == 'SESSION_EXPIRED'){
                                $('a[href="#collapse-payment-method"]').click();
                            }

                            if(eventName == 'APP_CLOSED'){
                                $("#paywithpaytm").addClass('paytmtoggle');
                                $("#paywithpaytm").attr('type','button');
                            } 
                        } 
                    }
                    };
                
                    
                    if(window.Paytm && window.Paytm.CheckoutJS){
                            // initialze configuration using init method 
                            window.Paytm.CheckoutJS.init(config).then(function onSuccess() {
                            // after successfully update configuration invoke checkoutjs
                            $('.paytm-pg-loader').hide();
                            window.Paytm.CheckoutJS.invoke();
                            }).catch(function onError(error){
                                console.log("error => ",error);
                            });
                    }  



                }
            }
        });
    }
);
