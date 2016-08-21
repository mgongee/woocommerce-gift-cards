<?php

class DatamatchGiftCards {
	
	public static $prefix = 'dtm_';
	
	/**
	 *  array( optionId  ) 
	 */
	public static $pluginSettings = array(
		'api_key',
		'enable_giftcard_cartpage',
		'enable_giftcard_tax_payment'
	);
	
	
	/**
	 * Gets plugin options and settings.
	 * @return array
	 */
	public static function getSettings() {

		$settings = array();
		foreach (self::$pluginSettings as $settingId) {
			$settings[$settingId] = get_option(self::$prefix . $settingId);	
		}
		return $settings;
	}
	
	public function displayFieldSet($fieldSet) {
		foreach ($fieldSet as $field) {
			$value = $this->settings[$field['id']];
			if ((!$value) && ($field['type'] != 'checkbox')) {
				$value = isset($field['default']) ? $field['default'] : '';
			}
			echo $this->displayField($field, $value);
		}
	}
	
	/**
	 * Generates HTML code for input row in table
	 * @param array $field
	 * @param array $value
	 */
	public function displayField($field, $value) {
		$label = __($field['label'], 'dtm_gift_cards');
		
		$value = htmlspecialchars($value);
		$field['name'] = self::$prefix . $field['id'];
		
		// 1. Make HTML for input
		switch ($field['type']) {
			case 'text':
				$inputHTML = $this->makeTextField($field, $value);
				break;
			case 'dropdown':
				$inputHTML = $this->makeDropdownField($field, $value);
				break;
			case 'textarea':
				$inputHTML = $this->makeTextareaField($field, $value);
				break;
			case 'checkbox':
				$inputHTML = $this->makeCheckboxField($field, $value);
				break;
			case 'hidden':
				$inputHTML = $this->makeHiddenField($field, $value);
				break;
			default:
				$inputHTML = '[Unknown field type "' . $field['type'] . '" ]';
		}
		
		
		// 2. Make HTML for table row
		switch ($field['type']) {
			case 'hidden':
				$tableRowHTML = <<<EOT
		<tr class="row-hidden">
			<td colspan="3" class="col-hidden">{$inputHTML}</td>
		</tr>
EOT;
				break;
			case 'text':
			case 'textarea':
			case 'checkbox':
			default:
				if (isset($field['description'])) {
					$tableRowHTML = <<<EOT
		<tr>
			<td class="col-name"><label for="dtm_{$field['id']}">$label</label></td>
			<td class="col-input">{$inputHTML}</td>
			<td class="col-info">
				<a href="javascript:void(0)" class="aops-tooltip">
					<span class="info-icon">?</span>
					<span class="tooltip-body">{$field['description']}</span>
				</a>
			</td>
		</tr>
EOT;
				}
				else {
				$tableRowHTML = <<<EOT
		<tr>
			<td class="col-name"><label for="dtm_{$field['id']}">$label</label></td>
			<td class="col-input">{$inputHTML}</td>
			<td class="col-info"></td>
		</tr>
EOT;
				}
		}

		return $tableRowHTML;
	}
	
	
	/**
	 * Generates HTML code for text field input
	 * @param array $field
	 * @param array $value
	 */
	public function makeTextField($field, $value) {
		$out = <<<EOT
			<input type="text" id="dtm_{$field['id']}" name="{$field['name']}" size="{$field['size']}" value="{$value}" class="dtm-text-field">
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for textarea input
	 * @param array $field
	 * @param array $value
	 */
	public function makeTextareaField($field, $value) {
		$out = <<<EOT
			<textarea id="dtm_{$field['id']}" name="{$field['name']}" cols="{$field['cols']}" rows="{$field['rows']}" value="">{$value}</textarea>
EOT;
		return $out;
	}
	
	/**
	 * Generates HTML code for checkbox 
	 * @param array $field
	 */
	public function makeCheckboxField($field, $value) {
		$chkboxValue = $value ? 'checked="checked"' : '';
		$out = <<<EOT
			<input type="checkbox" id="dtm_{$field['id']}" name="{$field['name']}" {$chkboxValue} value="1" class="dtm-checkbox-field"/>
EOT;
		return $out;
	}
	
	public static function log($data) {
		//$filename = pathinfo(__FILE__, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .'log.txt';
		//file_put_contents($filename, date("Y-m-d H:i:s") . " | " . print_r($data,1) . "\r\n\r\n", FILE_APPEND);
	}
}