# Woocommerce Shippify Plugin

![alt text](http://startupbrasil.org.br/wp-content/uploads/2014/12/shippify_logo_big.png "Shippify Logo")

- **Contributors:** [Leonardo Kuffo](https://github.com/lkuffo/), [Wander Bravo](https://github.com/bram70), [Jorge Cedeño](https://github.com/jorluis2695)
- **Tags:** Shipping, delivery, shippify
- **Version:** 1.2.5
- **Requires at least:** 4.5.7
- **Tested up to:** 4.7.4
- **Stable tag:** 4.7.4
- **License:** GNU General Public License v3.0
- **WC requires at least:** 3.0.5
- **WC tested up to:** 3.0.5

Deliver your WooCommerce shop products in less than 24h. Integration between WooCommerce and Shippify.

## Description ##

[Shippify](http://www.shippify.co/) is a technology company that offers their clients **Same-Day Delivery** shipping for their products. This plugin allows WooCommerce shop owners to use Shippify as a delivery method in their shop.

The plugin currently offers the following functionalities:

- Shippify as a shipping method in Checkout.
- WooCommerce Shipping Zones support.
- Shippify fares showed dinamically to the customers in checkout.
- Dispatch orders at the WooCommerce Admin orders page.
- Bulk orders dispatch.
- Shippify order status displayed at orders page.
- Supports Cash on Delivery.
- Multisite Support.


## Installation ##

### Installing the plugin: ###

1. Put the plugin files of this repository on the wp-content / plugins folder of wordpress.
2. In your wordpress admin panel go to Plugins -> Installed Plugins and Activate WooCommerce Shippify plugin.

### Settings ###

#### Requirements
##### [Shippify Account](http://shippify.co/companies#empresas-form)
     Before you can provide shippify as a shipping method in your shop, you need an APP ID and an APP SECRET.

To use the plugin correctly, the shop owner should follow these instructions to configure the settings:

1. Configure Shippify API Settings:
    1. In the admin panel, go to WooCommerce -> Settings -> Integration -> Shippify.
    2. Enter the APP ID and the APP SECRET provided to you, as they are in the Shippify Dashboard Settings.
    3. Save Changes.

2. Configure Shippify on Shipping Zones:
    1. In the admin panel, go to WooCommerce -> Settings -> Shipping Zones.
    2. Add a shipping zone. If you are new to shipping zones, [here](https://docs.woocommerce.com/document/setting-up-shipping-zones/) you can find a well detailed tutorial on how to set up shipping zones.
    3. Once you have created a shipping zone, click on Add Shipping Method and select Shippify. Enable the method if it isn't.
    4. Access to the Shippify settings by clicking on the method name in the *Shipping method title* column.
    5. In here you are going to enter the information of the **warehouse** from which you are going to dispatch your products among the shipping zone you configured. If you enter the Warehouse ID, there is no need for you to fill the other fields. **Make sure the warehouse provided match the shipping zone geographical location**, otherwise your clients aren't going to be able to place orders.
    6. Save Changes.

3. Configure google maps API key:
    1. Go to the Google maps and get your map api id [Google maps Account](https://developers.google.com/maps/documentation/javascript/get-api-key)
    2. Set the api key in the Google api key in Woocommerce / Settings / GOOGLE API KEY
    3. Save the changes.

4. You are ready to go! Please make sure that our shipping service is within the geographical limits of the configured shipping zones and warehouses.

### Recomendations ###

- Although is not required, we higly recommend to set the dimensions of all your products. If dimensions are not provided, MEDIUM size will be used to calculate fares.
- Make tests to make sure your shipping zones and warehouse are well configured.
- If there is an unexpected error or malfunctioning please [report us](https://shippify.slack.com/messages/integrations).

## FAQ ##

#### Shippify does not appear as a shipping method in checkout. ####
  - Check the configuration of your shipping zones. Selected country and/or entered zip code must match with the ones configured on the shipping zone.
  - Check in WooCommerce General Settings if you have specified your desired shipping location(s).

#### I'm not able to proceed in checkout, shippify warnings are appearing. ####
  - Keep in mind that if we are not able to make a Shippify route from pickup location (Warehouse configuration), to delivery location (Marker on the checkout map), you are not going to be able to proceed in checkout. Check your warehouse configuration of that shipping zone, check the delivery location marked on the integrated map, or make sure that Shippify service is available among those coordinates.  
  - Make sure you are filling the instruction fields, it is an obligatory field.
  - Make sure you entered the APP ID and the APP SECRET correctly.

#### Shipping fare is not appearing in checkout. ####
  - If the shipping cost of Shippify is not appearing on checkout, its because we are not able to make a Shippify route from pickup location (Warehouse configuration), to delivery location (Maker on the checkout map). Check your warehouse configuration of that shipping zone, check the delivery location marked on the integrated map, or make sure that Shippify service is available among those coordinates.
  - Maybe you have checked the option: "Store Pays the Delivery".
  - Make sure you entered the APP ID and the APP SECRET correctly.

#### Where I can find the shippify order ID of my woocommerce order? ####
  - If you have a dispatched order that is being shipped via-Shippify, the shippify order ID is available in the order details page.

#### I am getting weird error messages / I have a question not listed in here. ####
  - Please, feel free to [contact us](https://shippify.slack.com/messages/integrations) and remember we are working in more functionalities for the application.

## Comming Soon ##

- Shippify Order Filters
- Custom Setting: Shippify Fares by quotas on Checkout.

## Screenshots ##

### Shippify in Checkout Order Review
![alt text](https://cloud.githubusercontent.com/assets/550702/25629238/7d876504-2f2e-11e7-8183-6d0c92d8c867.png "Shipping option")

### Shippify Integration Setup
![alt text](https://cloud.githubusercontent.com/assets/550702/25857425/afc8f4ae-349e-11e7-8f9e-e469a764ffe0.png "API Setup")

### Shipping Zone Warehouse Setup
![alt text](https://cloud.githubusercontent.com/assets/550702/25629256/8a686340-2f2e-11e7-8d78-108d203a8855.png "Warehouse Setup")

### Shippify As a Shipping Method
![alt text](https://cloud.githubusercontent.com/assets/550702/25629260/8d47e32e-2f2e-11e7-8d77-846cb8c3fef9.png "Shippify As a Shipping Method")

### Dispatch Action at Orders
![alt text](http://i.imgur.com/dYlcw0O.png "Dispatch Action at Orders")

### Shippify Checkout Fields
![alt text](http://i.imgur.com/jCFLX9n.png "Shippify Checkout Fields")
