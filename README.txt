1. Copy "wp-content" folder to your Wordpress installation directory;
2. Add following line:

   include_once( 'gateways/paysera.php' );
	
in /wp-content/plugins/jigoshop/jigoshop.php file right after following text:

    include_once( 'gateways/skrill.php' );

3. In administration panel go to Jigoshop->Settings->Payment Gateways and fill in
   all required data under "Webtopay Payment".


# -- UPDATES --

* 2013-05-28 / Callback improvements, removed merchant ID
* 2013-03-25 / FIX. Micro payment fix;
* 2013-05-14. Fix. Displays gateway in coupons now

If any problems occur please feel free to seek help via support@paysera.com
