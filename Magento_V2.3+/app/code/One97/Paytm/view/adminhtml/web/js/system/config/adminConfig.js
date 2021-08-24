require(['jquery'],function($){
    $(document).ready(function(){
        var baseURL = $("#baseURLForPaytm").val();
        var formId=$("#paytmLastUpdate").parents("form").attr("id");
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
    });
});