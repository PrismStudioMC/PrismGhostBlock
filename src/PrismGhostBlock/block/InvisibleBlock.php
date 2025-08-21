<?php

namespace PrismGhostBlock\block;

use customiesdevs\customies\block\BlockComponents;
use customiesdevs\customies\block\BlockComponentsTrait;
use customiesdevs\customies\block\component\CollisionBoxComponent;
use customiesdevs\customies\block\component\DisplayNameComponent;
use customiesdevs\customies\block\component\MaterialInstancesComponent;
use customiesdevs\customies\block\Material;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Transparent;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;

class InvisibleBlock extends Transparent implements BlockComponents
{
    use BlockComponentsTrait;

    public function __construct(int $id)
    {
        parent::__construct(
            new BlockIdentifier($id),
            "Invisible Block",
            new BlockTypeInfo(BreakInfo::pickaxe(5.0, ToolTier::IRON, 5.0))
        );
        $this->initComponent("prism:invisible_block");
        $this->addComponent(new DisplayNameComponent("tile.prism:invisible_block.name"));
        $this->addComponent(new CollisionBoxComponent(true, Vector3::zero(), Vector3::zero()));
        $this->addComponent(new MaterialInstancesComponent([new Material(Material::TARGET_ALL, "prism:invisible_block", Material::RENDER_METHOD_ALPHA_TEST)]));

        unset($this->components["minecraft:friction"]);
    }
}