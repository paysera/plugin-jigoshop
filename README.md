plugin-jigoshop
===============

Paysera.com payment gateway plugin for Wordpress Jigoshop

Requirements
------------

- Wordpress 3.5
- Jigoshop

Installation
------------

1. Download this repository as zip and extract "wp-content" folder into wordpress main directory;
2. Add following line:

   include_once( 'gateways/paysera.php' );

in /wp-content/plugins/jigoshop/jigoshop.php file right after following text:

    include_once( 'gateways/skrill.php' );

3. In administration panel go to Jigoshop->Settings->Payment Gateways and fill in
   all required data under "Paysera Payment".

Contacts
--------

If any problems occur please feel free to seek help via support@paysera.com