# Magento 1.X:- 
  
  1. Download the plugin.
  2. Extract the files from the downloaded folder.
  3. Copy App and Skin folder from the required Magento version and place them into the root folder. If prompted to overwrite the files, click yes.
  4. Login to Magento Admin Panel and choose Payments Methods
      System- > Configuration - > Payment Methods
  5. Enable Paytm option from Payment Methods
  6. Go to the Paytm PG Configuration and save below configuration

      * Enable Plugin          - Yes
      * New Order Status       - Processing
      * Title                  - Paytm PG
      * Merchant ID            - MID provided by Paytm
      * Merchant Key           - Key provided by Paytm
      * Transaction URL 
        * Staging     - https://securegw-stage.paytm.in/theia/processTransaction
        * Production  - https://securegw.paytm.in/theia/processTransaction
      * Transaction Status URL 
        * Staging     - https://securegw-stage.paytm.in/merchant-status/getTxnStatus
        * Production  - https://securegw.paytm.in/merchant-status/getTxnStatus
      * Website Name 
        * Webstag for Staging
        * Webprod for Production
      * Custom Callback Url    - No
      * Callback Url           - customized callback url(this is visible when Custom Callback Url is yes)
      * Industry Type 
        * Retail for staging 
        * Industry type for Production will be provided by Paytm

  7. Please note if you have Linux server, please make sure folder permission are set to 755 & file permission to 644.
  8. Once plugin is installed, please logout from the admin panel and clear the cache of the Magento.
  9. Your plug-in is installed now, you can now make payment with Paytm.

# In case of any query, please contact to Paytm.
