/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
require(['jquery'],function($){
    $('.fetchStatusBtn').click(function(){
        var currentURL=$(this).attr('moveURL');
        $.ajax({
            showLoader: true,
            url: currentURL,
        }).done(function (data) {
            if($.trim(data.responseTableBody)!=''){
                $('.paytmResponseTBody').html(data.responseTableBody);
            }
            if(data.response){
                $('.fetchStatusBtn').remove();
            }
        });
    });
});