# CustomItemLoader
Plugin for PocketMine-MP that make your own custom item with full feature!

<a href="https://poggit.pmmp.io/p/CustomItemLoader"><img src="https://poggit.pmmp.io/shield.state/CustomItemLoader"></a><a href="https://poggit.pmmp.io/p/CustomItemLoader"><img src="https://poggit.pmmp.io/shield.dl/CustomItemLoader"></a>

## Reference
All of these components were came from MCPE addon document and [wiki.vg](https://wiki.vg/Bedrock_Protocol)

## How to use

You can see all usage on [here](./CONFIGURATION.md)

You can see example on [example folder](./example)

## FAQ (Frequently Asked Questions)

* Q. My client got crash
* A. Maybe it was caused by wrong set up on config or your texture pixel amount. Try to reduce texture pixel or check the config.


* Q. Texture doesn't appear
* A. Check my example on [Example folder](./example)


* Q. Item name is displayed wrongly
* A. Make sure to register item name on en_US.lang or other language file

* Q. I set its config correctly or I used example but item texture does not appear
* A. If you are using this plugin behind proxy such as WDPE, you should run this plugin also on lobby server [L#50~54](https://github.com/alvin0319/CustomItemLoader/blob/master/src/alvin0319/CustomItemLoader/CustomItemLoader.php#L50)