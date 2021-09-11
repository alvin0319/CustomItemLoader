# CustomItemLoader
Plugin for PocketMine-MP that make your own custom item with full feature!

<a href="https://poggit.pmmp.io/p/CustomItemLoader"><img src="https://poggit.pmmp.io/shield.state/CustomItemLoader"></a><a href="https://poggit.pmmp.io/p/CustomItemLoader"><img src="https://poggit.pmmp.io/shield.dl/CustomItemLoader"></a>

# CustomItemLoader for PM3 is now on feature-freeze
Since PM4 is in the BETA and I use PM4 as my base, I will maintain the PM4 branch as default.

PM3 version will get only protocol update until PM4 releases.

If PM4 fully released, PM3 branch will be deleted.

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
* A. If you are using this plugin behind proxy such as WDPE, you should run this plugin also on lobby server [L#53~57](https://github.com/alvin0319/CustomItemLoader/blob/master/src/alvin0319/CustomItemLoader/CustomItemLoader.php#L53#L57)
