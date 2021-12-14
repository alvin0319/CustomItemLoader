# Basic config format
The following format should be provided to create Custom item
```yaml
items:
  <item_name>:
    id: <id>
    meta: <meta> # Not used
    namespace: <namespace>
    name: <name>
```

`id` property is used to identify item as integer. This must not duplicate with an existing item.

`meta` property is used to seperate item by meta. but not used for now.

`namespace` property is used to identify item as string. use your own namespace like `alvin0319:custom_item`

`name` property will be used to display item name.

# Additional Properties

## Major Properties
To set item's max stack size, you have to use `max_stack_size` to set item's max stack size

You can set it by adding
```yaml
max_stack_size: 64
```

If you want item that player can equip on offhand, you have to enable `allow_off_hand` to enable offhand support

You can set it by adding
```yaml
allow_off_hand: true
```

When you want to add your custom item to creative inventory, you have to enable `add_creative_inventory`
You can set it by adding
```yaml
add_creative_inventory: true
```

I suggest to use my [Offhand](https://poggit.pmmp.io/p/OffHand) to enable Offhand

To make an item usable for breaking blocks even in creative mode, you have to enable `can_destroy_in_creative`

You can enable it by adding
```yaml
can_destroy_in_creative: true
```

To make item like sword, You have to enable `hand_equipped`

```yaml
hand_equipped: true
```

## Durable item
To make durable item, you have to enable `durable` to make item as durable.

You can enable it by adding 
```yaml
durable: true
```
on item properties

If you want to set its durability, you can use `max_durability` to set item's durable.

You can set it by adding
```yaml
max_durability: 64
```

## Placeable item
To make placeable item, you have to enable `isBlock` to make item as placeable.

You can enable it by adding
```yaml
isBlock: true
```

Also you have to declare block id to make item as placeable. Otherwise, Item will not be placed because PMMP suppose it as air.

You can declare block id by adding
```yaml
blockId: <blockId>
```
`<blockId>` must be positive integer

## Food item
To make food item, you have to enable `food` to make item as food.

You can enable it by adding
```yaml
food: true
```

You can edit food's ability by adding some attributes.

```yaml
can_always_eat: true
nutrition: 1
saturation: 2
residue:
  id: 1
  meta: 0
```

`can_always_eat` will make item as always edible. This means you can eat this item also in creative mode.

`nutrition` will fill food progress

`saturation` will fill saturation progress

`residue` will give item when player eats food like beetroot soup

## Tool item
To make item as tool, You have to enable `tool`

```yaml
tool: true
```

If item is tool, you can set the item's mining speed too.

```yaml
mining_speed: 2
```

There are some properties that you have to add

`tool_type` is used to identify tool type, like sword or pickaxe

Types:

`0`: None
`1`: Sword
`2`: Shovel
`4`: Pickaxe
`8`: Axe
`16`: Shears
`32`: Hoe

`tool_tier` is used to identify tool tier, like wooden or diamond

Types:
`1`: Wooden
`2`: Gold
`3`: Stone
`4`: Iron
`5`: Diamond


## Armor item
To make item as armor, you have to enable `armor`
```yaml
armor: true
```
Armor also can have durability, as I mentioned on [Tool](#Tool item) and [Durable](#Durable item)

On armor, you have to define these properties.

```yaml
armor_slot: chest
armor_class: diamond
```

Currently, I don't know what `armor_class` does. According to [bedrock.dev](https://bedrock.dev) document, It says it decides the texture type of armor.
> Texture Type to apply for the armor

But I can't see what's difference.

Allowed values for `armor_class`:

- gold
- none
- leather
- chain
- iron
- diamond
- elytra
- turtle
- netherite

The default value is `diamond`.

`armor_slot` is the slot of armor, which is applied where armor can be equipped.

Allowed values for `armor_slot`:
- helmet
- chest
- leggings
- boots

# Templates
You can use this template to make your custom item

Replace `<something>` to your own value

## Durable

```yaml
items:
  <your_item_name>:
    id: <id>
    meta: <meta>
    namespace: <namespace>
    name: <name>
    durable: true
    max_durablity: 32
    max_stack_size: 64
```

## Food

```yaml
items:
  <your_item_name>:
    id: <id>
    meta: <meta>
    namespace: <namespace>
    name: <name>
    food: true
    can_always_eat: true
    nutrition: 3
    saturation: 10
    residue:
      id: 1
      meta: 0
```

## Placeable item
```yaml
items:
  <your_item_name>:
    id: <id>
    meta: <meta>
    namespace: <namespace>
    name: <name>
    isBlock: true
    blockId: 1
```

## Tool
```yaml
items:
  <your_item_name>:
    id: <id>
    meta: <meta>
    namespace: <namespace>
    mining_speed: <speed>
    tool_type: 4 # This will make this tool as pickaxe, replace it as you want
    tool_tier: 5 # This will make this tool's tier as Diamond, replace it as you want
    tool: true
```

## Armor
```yaml
items:
  <your_item_name>:
    id: <id>
    meta: <meta>
    namespace: <namespace>
    armor: true
    armor_slot: chest
    defence_points: 5 # Defence points of armor
    armor_class: diamond # Default is diamond, feel free to send feedback to me
```