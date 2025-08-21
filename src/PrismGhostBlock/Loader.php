<?php

namespace PrismGhostBlock;

use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\item\CreativeInventoryInfo;
use muqsit\asynciterator\AsyncIterator;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\tile\TileFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\utils\SingletonTrait;
use PrismGhostBlock\block\GhostBlock;
use PrismGhostBlock\block\InvisibleBlock;
use PrismGhostBlock\listener\EventListener;
use PrismGhostBlock\tile\GhostBlockTile;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Symfony\Component\Filesystem\Path;

class Loader extends PluginBase
{
    use SingletonTrait;

    private ?AsyncIterator $asyncIterator = null;

    /**
     * @return void
     */
    protected function onLoad(): void
    {
        self::setInstance($this);
        $this->saveResource("pack.zip");
    }

    /**
     * @return void
     */
    protected function onEnable(): void
    {
        if(!class_exists(CustomiesBlockFactory::class)) {
            $this->getLogger()->error("Customies Block Factory not found. Please install the Customies plugin.");
            return;
        }

        if(class_exists(AsyncIterator::class)) {
            $this->getLogger()->info("AsyncIterator found. This will improve performance for ghost blocks.");
            $this->asyncIterator = new AsyncIterator($this->getScheduler());
        } else {
            $this->getLogger()->warning("AsyncIterator not found. This may cause performance issues. Please install the AsyncIterator virion for better performance.");
        }

        $tileFactory = TileFactory::getInstance();
        $tileFactory->register(GhostBlockTile::class, ["prism:ghost_block"]);

        $blockFactory = CustomiesBlockFactory::getInstance();
        $ghostBlockTypeId = BlockTypeIds::newId();
        $invisibleBlockTypeId = BlockTypeIds::newId();;

        $blockFactory->registerBlock(
            static fn() => new GhostBlock($ghostBlockTypeId),
            "prism:ghost_block",
            new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_CONSTRUCTION)
        );
        $blockFactory->registerBlock(
            static fn() => new InvisibleBlock($invisibleBlockTypeId),
            "prism:invisible_block",
            new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_CONSTRUCTION)
        );

        new EventListener($this);
        $this->loadResourcePack();
    }

    private function loadResourcePack(): void
    {
        $manager = $this->getServer()->getResourcePackManager();

        try {
            $reflectionClass = new \ReflectionClass(ResourcePackManager::class);
        } catch (ReflectionException $e) {
            $this->getLogger()->error("Failed to reflect ResourcePackManager: " . $e->getMessage());
            return;
        }

        $path = Path::join($this->getDataFolder(), "pack.zip");

        try {
            /** @var ResourcePack $pack */
            $pack = $reflectionClass->getMethod("loadPackFromPath")->invoke($manager, $path);
        } catch (ReflectionException $e) {
            $this->getLogger()->error("Failed to load resource pack: " . $e->getMessage());
            return;
        }

        $manager->setResourceStack(array_merge($manager->getResourceStack(), [$pack]));
    }

    /**
     * @return AsyncIterator|null
     */
    public function getAsyncIterator(): ?AsyncIterator
    {
        return $this->asyncIterator;
    }
}