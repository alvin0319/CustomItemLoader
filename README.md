# CustomItemLoader
Plugin for PocketMine-MP that makes your custom item with the full feature!

<a href="https://poggit.pmmp.io/p/CustomItemLoader"><img src="https://poggit.pmmp.io/shield.state/CustomItemLoader"></a><a href="https://poggit.pmmp.io/p/CustomItemLoader"><img src="https://poggit.pmmp.io/shield.dl/CustomItemLoader"></a>


#### NOTE: This branch is for PocketMine-MP v4.0, if you are looking for PocketMine-MP v3.0, please go to [master](https://github.com/alvin0319/CustomItemLoader/tree/master) branch.

# Supported branches

|name|description|
|---|---|
|pm4|This branch, Mainly maintain this branch.|
|master|For PM3, no future features will be added.|
|bleeding-edge|For PM4, which added a various unknown features, do not use this branch on production.|


## Reference
All of these components were came from MCPE addon document and [wiki.vg](https://wiki.vg/Bedrock_Protocol)

## How to use

You can see all usage on [here](./CONFIGURATION.md)

You can see example on [example folder](./example)

## FAQ (Frequently Asked Questions)

* Q. My client got crash
* A. Maybe it was caused by the wrong setup on config or your texture pixel amount. Try to reduce texture pixel or check the config.


* Q. Texture doesn't appear
* A. Check my example on [Example folder](./example)


* Q. Item name is displayed wrongly
* A. Make sure to register item name on en_US.lang or other language files

* Q. I set its config correctly or I used an example but item texture does not appear
* A. If you are using this plugin behind a proxy such as WDPE, you should run this plugin also on the lobby server [L#53~57](https://github.com/alvin0319/CustomItemLoader/blob/master/src/alvin0319/CustomItemLoader/CustomItemLoader.php#L53#L57)
