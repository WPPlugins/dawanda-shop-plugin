=== DaWanda shop plugin ===
Contributors: DMKE
Tags: sidebar, shop, dawanda
Requires at least: 2.2
Tested up to: 2.6.2
Stable tag: 2.0

DaWanda.com is the german version of etsy.com. This plugin makes your products available for your sidebar.

== Description ==

If you have an account at [DaWanda](http://dawanda.com) and also a shop, you'll be able to include your products
from DaWanda in (e.g.) your sidebar.

= Features =

*   Widget-ready: As of version 2.0 you can use this Plugin as widget.
*   Display the price (or display it not)
*   Prepared to use with a Lightbox plugin
*   The plugin produces vaild (X)HTML output
*   It provides many CSS selectors
*   This shop plugin is also prepared for localization, English and German language are included
*   To setup your plugin it provides an own configuration submenu
*   **Important:** To grab the data from the DaWanda servers, your PHP installation must allow opening remote URL (`allow_url_fopen`) ans support SimpleXML (as of PHP 5)

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the directory `dmke-dawanda` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure it through the 'DaWanda Configuration' submenu in 'Plugins'
4. Wheather you will use the Template Tag, place `<?php if(function_exists('dmke_dawanda_template_tag')) { dmke_dawanda_template_tag(); } ?>` in your templates, e.g. your `sidebar.php`. Or configure it as widget.

You'll be able to use CSS (optional)

1. Open your theme's `style.css` to edit it (e.g. in the 'Presentation' menu in WordPress)
2. Add the following lines of code to the end:
    /* DaWanda shop style */
    div.dmke-dawanda-item {
        
    }
    div.dmke-dawanda-item a.dmke-dawanda-picture-link {
        border: none;
        display: block;
    }
    div.dmke-dawanda-item a.dmke-dawanda-picture-link img {
        width: 160px;
        height: 120px;
        border: none;
    }
    div.dmke-dawanda-item span.dmke-dawanda-price {
        float: left;
        clear: right;
        width: 70px;
    }
    div.dmke-dawanda-item a.dmke-dawanda-shop-link {
        float: right;
        clear: both;
        width: 80px;
    }
3. Save your theme's `style.css`
