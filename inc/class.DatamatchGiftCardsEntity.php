<?php 

class DatamatchGiftCardsEntity {
	
	public $id = 0;
	public $longid = 0;
	public $value = 0; // float
	public $valid = false;
	
	/**
	 * @param string $giftCardId
	 */
	public function __construct($giftCardId) {
		if ($giftCardId) {
			$this->id = $giftCardId;
			$this->valid = DatamatchGiftCardsApi::checkGiftCardId($giftCardId);
			if ($this->valid) {
				$this->value = DatamatchGiftCardsApi::prepareGiftCardValue(
					DatamatchGiftCardsApi::getGiftCardBalance($giftCardId)
				);
				$this->longid = DatamatchGiftCardsApi::getGiftCardLongId($giftCardId);
			}
		}
	}
	
	public function isValid() {
		return (bool)$this->valid;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function updateValue($newValue) {
		$this->value = DatamatchGiftCardsApi::prepareGiftCardValue(
			DatamatchGiftCardsApi::updateGiftCardBalance($this->id, $newValue)
		);
	}
	
	public function decreaseValue($payment) {
		$newValue = $this->value - $payment;
		$this->updateValue($newValue);
	}
	
	public function getLongId() {
		return $this->longid;
	}
	
}