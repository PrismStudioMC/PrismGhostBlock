<?php

namespace PrismGhostBlock\listener;

use customiesdevs\customies\block\CustomiesBlockFactory;
use muqsit\asynciterator\handler\AsyncForeachResult;
use pocketmine\block\Air;
use pocketmine\block\tile\Tile;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerPostChunkSendEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\BlockTranslator;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use PrismGhostBlock\block\GhostBlock;
use PrismGhostBlock\Loader;
use PrismGhostBlock\tile\GhostBlockTile;

class EventListener
{
    public function __construct(
        private Loader $loader
    )
    {
        $this->loader->getServer()->getPluginManager()->registerEvent(
            DataPacketSendEvent::class,
            $this->onDataPacketSend(...),
            EventPriority::LOWEST,
            $this->loader,
        );
        $this->loader->getServer()->getPluginManager()->registerEvent(
            PlayerPostChunkSendEvent::class,
            $this->onPlayerPostChunkSend(...),
            EventPriority::LOWEST,
            $this->loader,
        );
    }

    /**
     * @param DataPacketSendEvent $ev
     * @return void
     */
    public function onDataPacketSend(DataPacketSendEvent $ev): void
    {
        $packets = $ev->getPackets();
        $targets = $ev->getTargets();

        foreach ($packets as $packet) {
            foreach ($targets as $origin) {
                if (!$packet instanceof UpdateBlockPacket) {
                    // If the packet is not an UpdateBlockPacket, we do not handle this event.
                    continue;
                }

                if ($packet->dataLayerId === UpdateBlockPacket::DATA_LAYER_LIQUID) {
                    // If the packet is for the liquid data layer, we do not handle this event.
                    continue;
                }

                if (!$origin->isConnected()) {
                    // If the origin is not connected, we do not handle this event.
                    continue;
                }

                $player = $origin->getPlayer();
                if ($player == null) {
                    // If the origin is not a player, we do not handle this event.
                    continue;
                }

                $this->processPacket($origin, $packet);
            }
        }
    }

    /**
     * @param NetworkSession $origin
     * @param UpdateBlockPacket $packet
     * @return void
     */
    private function processPacket(NetworkSession $origin, UpdateBlockPacket $packet): void
    {
        $world = $origin->getPlayer()->getWorld();
        $position = new Vector3($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());

        $block = $world->getBlock($position);
        $translator = $origin->getTypeConverter()->getBlockTranslator();

        if (!$block instanceof GhostBlock) {
            $origin->sendDataPacket(UpdateBlockPacket::create(
                $packet->blockPosition,
                $translator->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId()),
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_LIQUID
            ));
            return;
        }

        [$primary, $secondary] = $this->processBlock(
            $position,
            $world,
            $translator
        );

        $packet->blockRuntimeId = $primary->blockRuntimeId;
        $packet->dataLayerId = UpdateBlockPacket::DATA_LAYER_NORMAL;

        $origin->sendDataPacket($secondary);
    }

    /**
     * @param PlayerPostChunkSendEvent $ev
     * @return void
     */
    public function onPlayerPostChunkSend(PlayerPostChunkSendEvent $ev): void
    {
        $player = $ev->getPlayer();
        $world = $player->getWorld();

        $chunk = $world->getChunk($ev->getChunkX(), $ev->getChunkZ());
        if (!$chunk instanceof Chunk) {
            return;
        }

        $tiles = $chunk->getTiles();
        if (empty($tiles)) {
            return;
        }

        $asyncIterator = Loader::getInstance()->getAsyncIterator();
        if ($asyncIterator !== null) {
            $asyncIterator->forEach(new \ArrayIterator($tiles))->as(function (mixed $k, Tile $tile) use ($player): AsyncForeachResult {
                if (!$player->isConnected()) {
                    return AsyncForeachResult::INTERRUPT();
                }

                if (!$tile instanceof GhostBlockTile) {
                    return AsyncForeachResult::CONTINUE();
                }

                $this->processSendingChunk($tile, $player);
                return AsyncForeachResult::CONTINUE();
            });
            return;
        }

        foreach ($tiles as $tile) {
            if (!$player->isConnected()) {
                break;
            }

            if (!$tile instanceof GhostBlockTile) {
                continue;
            }

            $this->processSendingChunk($tile, $player);
        }
    }

    /**
     * @param GhostBlockTile $tile
     * @param Player $player
     * @return void
     */
    private function processSendingChunk(GhostBlockTile $tile, Player $player): void
    {
        $pos = $tile->getPosition();
        $networkSession = $player->getNetworkSession();
        $world = $player->getWorld();
        $block = $world->getBlock($pos);

        if (!$block instanceof GhostBlock) {
            return;
        }

        foreach ($this->processBlock($pos, $world, $networkSession->getTypeConverter()->getBlockTranslator()) as $packet) {
            $networkSession->sendDataPacket($packet);
        }
    }

    /**
     * @param Vector3 $pos
     * @param World $world
     * @param BlockTranslator $translator
     * @return UpdateBlockPacket[]
     */
    private function processBlock(Vector3 $pos, World $world, BlockTranslator $translator): array
    {
        $factory = CustomiesBlockFactory::getInstance();

        $fallback = null;
        $found = null;

        foreach ($pos->sidesArray() as $adjPos) {
            $adjBlock = $world->getBlock($adjPos);
            if ($adjBlock instanceof Air) continue;

            if (!$adjBlock instanceof GhostBlock) {
                $fallback = $adjBlock;
                break;
            }

            if ($found === null) {
                $found = $adjBlock->getDisplayedBlock();
            }
        }

        $displayedBlock = $fallback ?? $found ?? $factory->get("prism:ghost_block");
        $blockPosition = new BlockPosition($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());

        return [
            UpdateBlockPacket::create(
                $blockPosition,
                $translator->internalIdToNetworkId($factory->get("prism:invisible_block")->getStateId()),
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_NORMAL
            ),
            UpdateBlockPacket::create(
                $blockPosition,
                $translator->internalIdToNetworkId($displayedBlock->getStateId()),
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_LIQUID
            )
        ];
    }
}