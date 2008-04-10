<?php

$data_array = array();

// ebay
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 1382;
$advertiser_array['ALT'] = '3...2...1...meins! eBay';
$advertiser_array['NAME'] = 'ebay';
$advertiser_array['URL'] = 'http://www.ebay.de/';
$data2_array['advertiser'] = $advertiser_array;
unset($advertiser_array);
$banner_array = array(601);
$data2_array['468x60']['javascript'] = $banner_array;
unset($banner_array);
$banner_array = array(16);
$data2_array['468x60']['rotation'] = $banner_array;
unset($banner_array);
$banner_array = array(255);
$data2_array['234x60']['javascript'] = $banner_array;
unset($banner_array);
$banner_array = array(671);
$data2_array['logo']['normal'] = $banner_array;
unset($banner_array);
$data2_array['468x60']['spezial'] = '<script type="text/javascript">document.write(\'<scr\'+\'ipt src="http://banners.webmasterplan.com/view.asp?site=1382&ref=281193&type=text&tnb=32&showJS=1&template=1674&refurl=\'+escape(document.location.href)+\'"></scr\'+\'ipt>\'); </script>';
$data_array[1382] = $data2_array;
unset($data2_array);

// bücher.de
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 3780;
$advertiser_array['ALT'] = 'buecher.de - Bücher - Online - Portofrei';
$advertiser_array['NAME'] = 'buecher.de GmbH & Co. KG';
$advertiser_array['URL'] = 'http://www.buecher.de/';
$data2_array['advertiser'] = $advertiser_array;
unset($advertiser_array);
$banner_array = array(4);
$data2_array['468x60']['javascript'] = $banner_array;
unset($banner_array);
$banner_array = array(2);
$data2_array['234x60']['javascript'] = $banner_array;
unset($banner_array);
$banner_array = array(8);
$data2_array['logo']['normal'] = $banner_array;
unset($banner_array);
$data_array[3780] = $data2_array;
unset($data2_array);

// buch24.de
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 2176;
$advertiser_array['ALT'] = 'Buch24.de - Bücher versandkostenfrei';
$advertiser_array['NAME'] = 'Buch24.de';
$advertiser_array['URL'] = 'http://www.buch24.de/';
$data2_array['advertiser'] = $advertiser_array;
unset($advertiser_array);
$banner_array = array(5,16,18,19);
$data2_array['468x60']['normal'] = $banner_array;
unset($banner_array);
$banner_array = array(1,31,35);
$data2_array['234x60']['normal'] = $banner_array;
unset($banner_array);
$banner_array = array(50);
$data2_array['logo']['normal'] = $banner_array;
unset($banner_array);
$data_array[2176] = $data2_array;
unset($data2_array);

// booklooker
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 2650;
$advertiser_array['ALT'] = 'booklooker.de - Der Flohmarkt für Bücher';
$advertiser_array['NAME'] = 'booklooker';
$advertiser_array['URL'] = 'http://www.booklooker.de/';
$data2_array['advertiser'] = $advertiser_array;
unset($advertiser_array);
$banner_array = array(1,2,3);
$data2_array['468x60']['normal'] = $banner_array;
unset($banner_array);
$banner_array = array(5,6);
$data2_array['234x60']['normal'] = $banner_array;
unset($banner_array);
$data_array[2650] = $data2_array;
unset($data2_array);

// stellenmarkt.de
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 2891;
$advertiser_array['ALT'] = 'Stellenmarkt.de - Stellenangebote und Gesuche';
$advertiser_array['NAME'] = 'StellenMarkt.de';
$advertiser_array['URL'] = 'http://www.stellenmarkt.de/';
$data2_array['advertiser'] = $advertiser_array;
unset($advertiser_array);
$banner_array = array(20,29);
$data2_array['468x60']['normal'] = $banner_array;
unset($banner_array);
$banner_array = array(26,45);
$data2_array['234x60']['normal'] = $banner_array;
unset($banner_array);
$data_array[2891] = $data2_array;
unset($data2_array);

// getPrice
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 3231;
$advertiser_array['ALT'] = 'getprice.de - finden, vergleichen, sparen';
$advertiser_array['NAME'] = 'GetPrice.de GbR';
$advertiser_array['URL'] = 'http://www.getprice.de/';
$data2_array['advertiser'] = $advertiser_array;
unset($advertiser_array);
$banner_array = array(14,16,17);
$data2_array['468x60']['javascript'] = $banner_array;
unset($banner_array);
$banner_array = array(11,13,15);
$data2_array['234x60']['javascript'] = $banner_array;
unset($banner_array);
$banner_array = array(11);
$data2_array['logo']['normal'] = $banner_array;
unset($banner_array);
$data_array[3231] = $data2_array;

// wissen.de
$data2_array = array();
$advertiser_array = array();
$advertiser_array['SITE'] = 4009;
$advertiser_array['ALT'] = 'wissen.de';
$advertiser_array['NAME'] = 'wissen.de';
$advertiser_array['URL'] = 'http://www.wissen.de/';
$data2_array['advertiser'] = $advertiser_array;
$banner_array = array(19);
$data2_array['468x60']['normal'] = $banner_array;
unset($banner_array);
$banner_array = array(20);
$data2_array['234x60']['normal'] = $banner_array;
unset($banner_array);
$banner_array = array(17);
$data2_array['logo']['normal'] = $banner_array;
unset($banner_array);
$data_array[$advertiser_array['SITE']] = $data2_array;
unset($advertiser_array);
unset($data2_array);
?>