<?php

namespace PrismGhostBlock\tile;

use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;

class GhostBlockTile extends Tile
{
    public function readSaveData(CompoundTag $nbt): void {}
    protected function writeSaveData(CompoundTag $nbt): void {}
}