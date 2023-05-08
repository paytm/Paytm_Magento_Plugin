require(['jquery'],function($){
    jQuery(document).ready(function(){
        var baseURL = $("#baseURLForPaytm").val();
        var formId=$("#paytmLastUpdate").parents("form").attr("id");
        var dynamicId = document.forms['config-edit-form'].querySelector('span').id.split('_')[1];
        var reqURL=baseURL+"paytm/Standard/Curlconfig?getlastUpdate=1";
        $.ajax({
            showLoader: true,
            url: reqURL,
        }).done(function (data) {
            $("#phpCurlVersion").text("PHP cURL Version: "+data.phpCurlVersion);
            $("#magentoVersion").text("Magento Version: "+data.version);
            $("#paytmPluginVersion").text("Paytm Plugin Version: "+data.paytmPluginVersion);
            $("#paytmLastUpdate").text("Last Updated: "+data.lastupdate);
            $("#callBackUrl").text(data.callBackUrl+"?webhook=yes");
        });
        $("#"+formId).submit(function(e){
            var paytmEnable=$(".paytmEnbDrpDwn").val();
            if(paytmEnable=="1"){
                var currentURL=baseURL+"paytm/Standard/Curlconfig";
                $.ajax({
                    showLoader: true,
                    url: currentURL,
                }).done(function (data) {
                    if(data.responseTableBody!="All is done."){
                        alert(data.responseTableBody);
                    }
                });
            }
        });

        jQuery('.paytmEnbDrpDwn').change(function(){
            if (jQuery('.paytmEnbDrpDwn').val()==1) {
            //do nothing
            }else{
                if (confirm('Are you sure you want to disable Paytm Payment Gateway, you will no longer be able to accept payments through us?')) {
                //disable pg
                }else{
                    jQuery('.paytmEnbDrpDwn').val(1);
                }    
            }
        });

        //webhook configuration
        jQuery('#payment_'+dynamicId+'_paytm_iswebhook').change(function(){

            var is_webhook = ''; 
            var environment  =jQuery('#payment_'+dynamicId+'_paytm_environment').val();
            var mid  =jQuery('#payment_'+dynamicId+'_paytm_MID').val();   
            var merchant_key  =jQuery('#payment_'+dynamicId+'_paytm_merchant_key').val();   
            var webhookUrl  =baseURL+"paytm/Standard/Response/?webhook=yes";
          
            if (jQuery('#payment_'+dynamicId+'_paytm_iswebhook').val()==1) {
                is_webhook = 1;
            }else{
                is_webhook = 0;
            }
            var reqURLWebhook = baseURL+"paytm/Standard/Curlconfig?setPaymentNotificationUrl";
            jQuery.ajax({
                type:"POST",
                dataType: 'json',
                data:{is_webhook:is_webhook,mid:mid,merchant_key:merchant_key,environment:environment,webhookUrl:webhookUrl},
                url: reqURLWebhook,
                async:false,
                success: function(data) {
                    if (data.message == true) {
                        //jQuery('.webhook-message').html('<div class="paytm_response success-box">WebhookUrl updated successfully</div>');
                        //alert("WebhookUrl updated successfully");
                    } else {
                        //document.getElementById("woocommerce_paytm_iswebhook").checked = false;
                        //jQuery('.webhook-message').html('<div class="paytm_response error-box">'+data.message+'</div>');
                    }

                    if(data.showMsg == true){
                        document.getElementById("woocommerce_paytm_iswebhook").checked = false;
                        alert(data.message);
                        window.open('https://dashboard.paytm.com/next/webhook-url', '_blank');
                    }
                },
                complete: function() { 
                    return true;
                 }
            });
        });
    });
});