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

//require(__DIR__ . "\..\Stream.php");
class PlayerHandler extends \Server\Server {
	protected $players = array();
	protected $active_sessions = 0;

	public function __construct() {}

	/**
	 * 
	 * Add a client to the handler
	 * 
	 */
	public function add($socket, \Server\Server $server, \Server\SQL $sql) {
		$player = new Player($socket, $this->active_sessions, $server, $sql, $this);
		$server->handleModules('__onConnect', $socket, $this);
	}

	/**
	 *
	 * Remove all null players
	 * 
	 */
	protected function cycleEvent() {
		$playerCount = count($this->players);
		$this->players = array_values($this->players);

		for($x = 0; $x < $playerCount; $x++) {
			if(!isset($this->players[$x])) {
				continue;
			}

			if(time() - $this->players[$x]->getLastPacket() > 120) {
				socket_close($this->players[$x]->getConnection());
				$this->log('Closing ' . $this->players[$x]->getUsername() .'\'s connection');
				$this->active_sessions--;
				unset($this->players[$x]);
				continue;
			}

			@socket_send($this->players[$x]->connection, " ", 1, MSG_OOB);
			if(socket_last_error($this->players[$x]->connection) != 0) {
				$this->active_sessions--;
				$this->players[$x] = null;
				unset($this->players[$x]);
			}
		}
	}

	public function modActiveSessions($number) {
		$this->active_sessions += intval($number);
	}

	public function addConnection(Player $player) {
		$this->players[] = $player;
	}

	public function getPlayers() {
		return $this->players;
	}
		
	public function update(\Server\Client\Player $player, \Server\Stream $out, \Server\Cryption\ISAAC $isaac) {
		$block = new \Server\Stream();
		$players = $this->getPlayers();
		
		$out->beginPacket($isaac, 81);
		$out->iniBitAccess();
		
		$this->updateLocalMovement($player, $out);
		//	if (player.isUpdateRequired()) {
		//		PlayerUpdating.updateState(player, block, false, true);
		//	}
		
		$out->putBits(8, 0);
		foreach($players as $plr) {
			if(false) {
				//	PlayerUpdating.updateOtherPlayerMovement(other, out);
				//	if (other.isUpdateRequired()) {
				//		PlayerUpdating.updateState(other, block, false, false);
				//	}
			} else {
				$out->putBit(true);
				$out->putBits(2, 3);
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
							
			$this->addPlayer($out, $player, $other);
			$this->updateState($player, $block, $forceAppearance, $noChat);
		}
		
		if($block->getCurrentOffset() > 0) {
			$out->putBits(11, 2047);
			$out->finishBitAccess();
			$out->putBytes($block->getStream());
		} else {
			$out->finishBitAccess();
		}
		
		$out->finishPacket();
		$this->writeSpec($out, $player);
	}
	
	public function write($s, \Server\Stream $out, $connection) {
		$out->clear();
		socket_write($connection, $s);
	}

	public function writeStream() {
		$stream = $this->outStream->getStream();
		$this->write($stream);
	}
	
	public function writeSpec(\Server\Stream $out, \Server\Client\Player $plr) {
		$stream = $out->getStream();
		$this->write($stream, $out, $plr->connection);
	}
	
	public function updateState(\Server\Client\Player $player, \Server\Stream $out, $forceAppearance, $noChat) {
		$mask = 0x0;
		
		//	if (player.isChatUpdateRequired() && !noChat) {
		//		mask |= 0x80;
		//	}
		//	if (player.isAppearanceUpdateRequired() || forceAppearance) {
		//		mask |= 0x10;
		//	}
		
		// Now, we write the actual mask.
		if (mask >= 0x100) {
		//	mask |= 0x40;
		//	block.writeShort(mask, StreamBuffer.ByteOrder.LITTLE);
		} else {
			$out->putByte(mask);
		}
		
		if (player.isChatUpdateRequired() && !noChat) {
			appendChat(player, block);
		}
		
		if (player.isAppearanceUpdateRequired() || forceAppearance) {
			appendAppearance(player, block);
		}
		//	if (player.isChatUpdateRequired() && !noChat) {
		//		appendChat(player, block);
		//	}

		//	if (player.isAppearanceUpdateRequired() || forceAppearance) {
		//		appendAppearance(player, block);
		//	}
	}
	
	public function addPlayer(\Server\Stream $out, \Server\Client\Player $player, \Server\Client\Player $other) {
		$out->putBits(11, 0);
		$out->putBit(true);
		$out->putBit(true);
		// Position delta = Misc.delta(player.getPosition(), other.getPosition());
		
		$out->putBits(5, 11);
		$out->putBits(5, 11);
	}
	
	public function updateLocalMovement(\Server\Client\Player $player, \Server\Stream $out) {
	$updateRequired = false;
		if(true) {
			$out->putBit(true);
			$x = 400;
			$y = 400;
			$this->appendPlacement($out,  $x, $y, 1, true, true);
		} else {
			$primaryDirection = -1;
			$secondaryDirection = -1;
			if($primaryDirection != -1) {
				$out->writeBit(true);
				if($secondaryDirection != -1) {
					$this->appendRun($out, $primaryDirection, $secondaryDirection, $updateRequired);
				} else {
					$this->appendWalk($out, $primaryDirection, $updateRequired);
				}
			} else {
				if($updateRequired) {
					$out->putBit(true);
					$this->appendStand($out);
				} else {
					$out->putBit(false);
				}
			}
		}	
	}
	
	public function appendStand(\Server\Stream $out) {
		$out->putBits(2,0);
	}
	
	public function appendRun(\Server\Stream $out, $primaryDirection, $secondaryDirection, $updateRequired) {}
	
	public function appendWalk(\Server\Stream $out, $primaryDirection, $updateRequired) {}
	
	public function appendPlacement(\Server\Stream $out, $x, $y, $z, $discardMovementQueue, $attributesUpdate) {
			$out->putBits(2, 3);
			$out->putBits(2, $z);
			$out->putBit($discardMovementQueue);
			$out->putBit($attributesUpdate);
			$out->putBits(7, $y);
			$out->putBits(7, $x);
	}
}
?>
