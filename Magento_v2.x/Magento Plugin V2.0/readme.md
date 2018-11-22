# Magento 2.0:- 
  
  1. Download the plugin.
  2. Extract the files from the downloaded folder.
  3. Copy App folder from the required Magento version and place them into the root folder. If prompted to overwrite the files, click yes.
  4. Run below command:
      php bin/magento module:enable One97_Paytm
      php bin/magento setup:upgrade
      php bin/magento setup:static-content:deploy
  5. Login to Magento Admin Panel and choose Payments Methods
      System- > Configuration - > Payment Methods
  6. Enable Paytm option from Payment Methods
  7. Go to the Paytm PG Configuration and save below configuration

      * Enable                  - Yes
      * Title                   - Paytm PG
      * Merchant ID             - MID provided by Paytm
      * Merchant Key            - Key provided by Paytm
      * Transaction URL         
        * Staging     - https://securegw-stage.paytm.in/theia/processTransaction
        * Production  - https://securegw.paytm.in/theia/processTransaction
      * Transaction Status URL  
        * Staging     - https://securegw-stage.paytm.in/merchant-status/getTxnStatus
        * Production  - https://securegw.paytm.in/merchant-status/getTxnStatus
      * Website Name            
        * Webstag for Staging
        * Webprod for Production
      * Custom Callback Url     - No
      * Callback Url            - customized callback url(this is visible when Custom Callback Url is yes)
      * Industry Type           
        * Retail for staging 
        * Industry type for Production will be provided by Paytm
      * Channel ID              - WEB/WAP
      * Sort Order              - 2
      * Applicable Country      - All allowed country

  8. Please note if you have Linux server, please make sure folder permission are set to 755 & file permission to 644.
  9. Once plugin is installed, please logout from the admin panel and clear the cache of the Magento.
  10. Your plug-in is installed now, you can now make payment with Paytm.

See Video : https://www.youtube.com/watch?v=bR18KwhY4V8

# In case of any query, please contact to Paytm.
