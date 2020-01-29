var url = window.location.protocol + "//" + window.location.host;
var path=document.location.pathname;
var pathArr=path.split('admin');
url+=pathArr[0]+"paytm/processing/versioncheck";
window.onload = function(e){ 
    versionLastUpdateAjax();
}
function versionLastUpdateAjax() {
	new Ajax.Request(url, {
		parameters: {isAjax: 1, method: 'POST'},
		onSuccess: function(transport) {
			var data=transport.responseJSON;
			var magentoVersion=document.getElementById('magentoVersion');
			var paytmLastUpdate=document.getElementById('paytmLastUpdate');
			var paytmPluginVersion=document.getElementById('paytmPluginVersion');
			var phpCurlVersion=document.getElementById('phpCurlVersion');
			if(magentoVersion!=null){
				magentoVersion.innerHTML = "Magento Version: "+data.version;
			}
			if(paytmLastUpdate!=null){
				paytmLastUpdate.innerHTML = "Last Updated: "+data.lastupdate;
			}
			if(paytmPluginVersion!=null){
				paytmPluginVersion.innerHTML = "Paytm Plugin Version: "+data.pluginVersion;
			}
			if(phpCurlVersion!=null){
				phpCurlVersion.innerHTML = "PHP cURL Version: "+data.phpCurlVersion;
			}
		}
	});
};