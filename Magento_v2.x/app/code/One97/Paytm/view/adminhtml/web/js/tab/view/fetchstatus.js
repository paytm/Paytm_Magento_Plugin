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