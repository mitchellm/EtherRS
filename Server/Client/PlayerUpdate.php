<?php
namespace Server\Client;

/**
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/mitchellm/EtherRS/
 */

class PlayerUpdate extends \Server\Client\PlayerHandler {
	protected $player, $enc, $player_handler;
	protected $block, $out;

	public function __construct(\Server\Client\Player $player) {
		$this->player = $player;
		$this->out = $player->getOutstream();
		$this->enc = $player->getEncryptor();
	}

	public function sendBlock() {
		$this->block = new \Server\Network\Stream();
		$players = $this->getPlayers();
		
		$this->out->beginPacket($this->enc, 81);
		$this->out->iniBitAccess();
		
		$this->updateLocalMovement();
		//	if (player.isUpdateRequired()) {
		//		PlayerUpdating.updateState(false, true);
		//	}
		
		$this->out->putBits(8, 0);
		foreach($players as $plr) {
			if(false) {
				//	PlayerUpdating.updateOtherPlayerMovement(other, out);
				//	if (other.isUpdateRequired()) {
				//		PlayerUpdating.updateState(false, false);
				//	}
			} else {
				$this->out->putBit(true);
				$this->out->putBits(2, 3);
			}
		}
		
		for($i = 0; $i < count($players); $i++) {
			//	if (player.getPlayers().size() >= 255) {
			//		// Player limit has been reached.
			//		break;
			//	}
			$other = $players[$i];
			if($other == null || $other == $player)
				continue;
							
			$this->addPlayer($other);
			$this->updateState($forceAppearance, $noChat);
		}
		
		if($this->block->getCurrentOffset() > 0) {
			$this->out->putBits(11, 2047);
			$this->out->finishBitAccess();
			$this->out->putBytes($this->block->getStream());
		} else {
			$this->out->finishBitAccess();
		}
		
		$this->out->finishPacket();
		//needs to be written to the socket
	}
	
	public function updateState($forceAppearance, $noChat) {
		$mask = 0x0;
		
		//	if (player.isChatUpdateRequired() && !noChat) {
		//		mask |= 0x80;
		//	}
		//	if (player.isAppearanceUpdateRequired() || forceAppearance) {
		//		mask |= 0x10;
		//	}
		
		// Now, we write the actual mask.
		if ($mask >= 0x100) {
		//	mask |= 0x40;
		//	block.writeShort(mask, StreamBuffer.ByteOrder.LITTLE);
		} else {
			$this->out->putByte($mask);
		}
	
		//	if (player.isChatUpdateRequired() && !noChat) {
		//		appendChat(player, block);
		//	}

		//	if (player.isAppearanceUpdateRequired() || forceAppearance) {
		//		appendAppearance(player, block);
		//	}
	}
	
	public function addPlayer(\Server\Client\Player $other) {
		$this->out->putBits(11, 0);
		$this->out->putBit(true);
		$this->out->putBit(true);
		// Position delta = Misc.delta(player.getPosition(), other.getPosition());
		
		$this->out->putBits(5, 11);
		$this->out->putBits(5, 11);
	}
	
	public function updateLocalMovement() {
		$updateRequired = false;
		if(true) {
			$this->out->putBit(true);
			$x = 400;
			$y = 400;
			$this->appendPlacement($x, $y, 1, true, true);
		} else {
			$primaryDirection = -1;
			$secondaryDirection = -1;
			if($primaryDirection != -1) {
				$this->out->writeBit(true);
				if($secondaryDirection != -1) {
					$this->appendRun($primaryDirection, $secondaryDirection, $updateRequired);
				} else {
					$this->appendWalk($primaryDirection, $updateRequired);
				}
			} else {
				if($updateRequired) {
					$this->out->putBit(true);
					$this->appendStand();
				} else {
					$this->out->putBit(false);
				}
			}
		}	
	}
	
	public function appendStand() {
		$this->out->putBits(2,0);
	}
	
	public function appendRun($primaryDirection, $secondaryDirection, $updateRequired) {}
	
	public function appendWalk($primaryDirection, $updateRequired) {}
	
	public function appendPlacement($x, $y, $z, $discardMovementQueue, $attributesUpdate) {
			$this->out->putBits(2, 3);
			$this->out->putBits(2, $z);
			$this->out->putBit($discardMovementQueue);
			$this->out->putBit($attributesUpdate);
			$this->out->putBits(7, $y);
			$this->out->putBits(7, $x);
	}
}