# Paytm Payment plugin for Magento version 2.4.x

## System requirements

- **Magento:** Open Source / Adobe Commerce **2.4.x** only (this package is not supported on Magento 2.3 or earlier).
- **PHP:** **8.0 or later** (PHP 7.x is not supported). For production, use a PHP version [supported by your Magento release](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/system-requirements.html) (many 2.4 patches require **8.1+**).
- **Module:** `PPSL_Paytm` — **v1.0.0** (first release for Magento 2.4.x). Copy from `app/code/PPSL` below.

## Installation

1. Copy the module into your Magento root:
   - Copy `app/code/PPSL` from this folder into `<magento-root>/app/code/PPSL`. Create `app/code` if it does not exist.

2. Run these commands from the Magento root:

```bash
php bin/magento module:enable PPSL_Paytm
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

3. In Admin, enable and configure Paytm under **Stores → Configuration → Sales → Payment Methods**.
