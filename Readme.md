# Chippin (for PrestaShop v.1.6)

### Introduction
PrestaShop is a free and open-source e-commerce web application. PrestaShop's extensibility revolves around modules, which are small programs that make use of PrestaShop's functionality and changes them or add to them in order to make PrestaShop easier to use or more customized. A **payment** module is a regular PrestaShop module, except that it extends the PaymentModule class instead of the Module class.

Chippin is a payment gateway to allow friends and family to make group purchases of gifts. This repository is a PrestaShop payment module that allows merchants (store owners) to give their customers the ability to pay for their products/services using Chippin.

### Installation
To use this repository, a merchant should first;
 - Download the repositiry and unzip within the `modules` directory
 - Rename the directory to `chippin`

Then in the admin-panel (back-office);
 - Navigate to **Modules and Services** -> **Payments**
 - Select **View all available payment solutions**
 - Click **Install** next to the Chippin payment module
 - Provide the merchants Chippin ID and Secret (which can be found at http://chippin.co.uk/admin)

In Chippin;
 - Provide Chippin with the URLs supplied to you in the PrestaShop back-office (they are under the heading **CHIPPIN CALLBACK URLS**)

Back in the PrestaShop back-office;
 - Be sure to have Chippin available (selected) in CURRENCY, GROUP and COUNTRY RESRICTIONS at **Modules and Services** -> **Payments** to make it an available payment method.

### Payment Statuses

There are currently 6 different payment statuses;
- Chippin initiated
- Chippin completed
- Chippin failed
- Chippin timed-out
- Chippin cancelled
- Chippin rejected

These statuses are based on the callbacks that are fired from Chippin at certain points through a Chippin process. A merchant can view the status of a Chippin at any point by going to **Orders** in the PrestaShop back-office.
