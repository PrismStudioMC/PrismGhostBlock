<?php

namespace PrismGhostBlock\block;

use customiesdevs\customies\block\BlockComponents;
use customiesdevs\customies\block\BlockComponentsTrait;
use customiesdevs\customies\block\component\DisplayNameComponent;
use customiesdevs\customies\block\component\MaterialInstancesComponent;
use customiesdevs\customies\block\Material;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Transparent;
use pocketmine\item\ToolTier;
use PrismGhostBlock\tile\GhostBlockTile;

class GhostBlock extends Transparent implements BlockComponents
{
    use BlockComponentsTrait;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        parent::__construct(
            new BlockIdentifier($id, GhostBlockTile::class),
            "Ghost Block",
            new BlockTypeInfo(BreakInfo::pickaxe(5.0, ToolTier::IRON, 5.0))
        );
        $this->initComponent("prism:ghost_block", false);
        $this->addComponent(new DisplayNameComponent("tile.prism:ghost_block.name"));

        $material = new Material(
            Material::TARGET_ALL,
            "prism:ghost_block",
            Material::RENDER_METHOD_ALPHA_TEST
        );
        $this->addComponent(new MaterialInstancesComponent([$material]));

        unset($this->components["minecraft:friction"]);
    }

    /**
     * @param int $depth
     * @return Block|null
     */
    public function getDisplayedBlock(int $depth = 0): ?Block
    {
        if ($depth > 3) {
            return null;
        }

        $world = $this->getPosition()->getWorld();
        $pos = $this->getPosition();

        $fallback = null;

        foreach ($pos->sidesArray() as $adjPos) {
            $adjBlock = $world->getBlock($adjPos);

            if ($adjBlock instanceof Air) continue;
            if (!$adjBlock instanceof GhostBlock) {
                return $adjBlock;
            }

            if ($fallback === null) {
                $fallback = $adjBlock->getDisplayedBlock($depth + 1);
            }
        }

        return $fallback;
    }
}