<?php

declare(strict_types=1);

namespace alvin0319\CustomItemLoader\data;

final class ItemComponent{

	public IconData $minecraft_icon;

	public CreativeCategoryData $minecraft_creative_category;

	public FoodData $minecraft_food;

	public bool $minecraft_allow_off_hand;

	public bool $minecraft_animates_in_toolbar;

	public bool $minecraft_can_destroy_in_creative;

	public string $minecraft_creative_group;

	public int $minecraft_damage;

	public string $minecraft_enchantable_slot;

	public int $minecraft_enchantable_value;

	public bool $minecraft_explodable;

	public bool $minecraft_foil;

	public int $minecraft_frame_count;

	public bool $minecraft_hand_equipped;

	public bool $minecraft_ignores_permissions;

	public bool $minecraft_liquid_clipped;

	public int $minecraft_max_stack_size;

	public float $minecraft_mining_speed;

	public bool $minecraft_mirrored_at;

	public bool $minecraft_requires_interact;

	public bool $minecraft_should_despawn;

	public bool $minecraft_stacked_by_data;

	public int $minecraft_use_animation;

	public int $minecraft_use_duration;
}