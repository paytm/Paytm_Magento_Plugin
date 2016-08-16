Supported Versions

Paytm Plugin version V1.0 Magento supported version 2.0.X onward



Installation and Configuration

upload app/code/One97 (all files and folder) at you server end.

Run below command:
php bin/magento module:enable One97_Paytm
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy

goto Admin->Store->Configuration->Sales->Payment Method->Paytm
fill details here and save them.

goto Admin->System->Cache Management
Clear all Cache.

Now you can collect payment via Paytm .



In case of any issue Kindly write us at integration.dev@paytm.com