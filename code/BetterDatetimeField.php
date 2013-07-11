<?php

/**
 * A better DatetimeField, because the core ones suck
 *
 * @todo Date picker in the front end
 * @todo Document
 * @todo Validate
 * @todo Set config via a function, not just through construct
 * @todo Tests
 *
 * @author Dan Hensby <dan@betterbrief.co.uk>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
class BetterDatetimeField extends FormField {

	protected
		$valueObj,
		$dbField,
		$showTime = true,
		$showDate = true,
		$config;

	private static
		$default_config = array(
			'date_field_classes' => 'date',
			'time_field_classes' => 'time',
			'dmy_fields' => true,
			'hour_interval' => 1,
			'minute_interval' => 15,
			'second_interval' => 1,
			'day_interval' => 1,
			'month_interval' => 1,
			'year_interval' => 1,
			'date_field_order' => array(
				'Day', 'Month', 'Year',
			),
			'24_hr' => true,
			'include_seconds' => false,
			'use_date_dropdown' => false,
			'max_dropdown_values' => array(
				'Day' => 31,
				'Month' => 12,
				'Year' => 2013,
				'Hour' => 23,
				'Minute' => 59,
				'Second' => 59,
			),
			'min_dropdown_values' => array(
				'Day' => 1,
				'Month' => 1,
				'Year' => 1900,
				'Hour' => 00,
				'Minute' => 00,
				'Second' => 00,
			),
			'max_date' => false,
			'min_date' => false,
			'max_time' => false,
			'min_time' => false,
			'min_age' => false,
			'max_age' => false,
		);

	public function __construct($name, $title = null, $value = null, $config = array()) {
		$this->config = array_merge(
			self::config()->get('default_config'),
			$config
		);
		parent::__construct($name, $title, $value);
	}

	/**
	 * Setup the field by passing in a DBField (Date, Time or SS_Datetime)
	 *
	 * @param Date|Time|SS_Datetime $field The DBField object to setup the field
	 */
	public function setDBFieldObject(DBField $field) {
		if ($field instanceof SS_Datetime) {
			$this->dbField = $field;
			$this->setShowTimeFields(true);
			$this->setShowDateFields(true);
		}
		elseif ($field instanceof Date) {
			$this->dbField = $field;
			$this->setShowTimeFields(false);
			$this->setShowDateFields(true);
		}
		elseif ($field instanceof Time) {
			$this->dbField = $field;
			$this->setShowTimeFields(true);
			$this->setShowDateFields(false);
		}
		else {
			throw new LogicException(
				__CLASS__ . ' only accepts Date, Time or SS_Datetime DBFields, '
				. var_export($field->class, true) . ' passed instead'
			);
		}
		return $this;
	}

	public function validateDateArray($value) {
		if (!is_array($value)) {
			return false;
		}
		$day = $month = $year = $hour = $min = $second = $period = null;
		$valid = true;
		if ($this->getShowDateFields()) {
			if ($this->config['dmy_fields']) {
				$day = !empty($value['Day']) ? $value['Day'] : null;
				$month = !empty($value['Month']) ? $value['Month'] : null;
				$year = !empty($value['Year']) ? $value['Year'] : null;
				// Handle people entering "2nd" or "third", or "1st"
				// and people entering Jan or January instead of 1 or 01
				if(!is_numeric($day) || !is_numeric($month)) {
					$timeFormat = "$day $month $year";
				}
				if(!isset($timeFormat)) {
					$timeFormat = "$year-$month-$day";
				}
				$strToTime = strtotime($timeFormat);
				if($strToTime !== false) {
					list($year, $month, $day) = explode('-', date('Y-m-d', $strToTime), 3);
				}
				else {
					$valid = false;
				}
			}
			else if(!empty($value['Date']) && $strToTime = strtotime($value['Date'])) {
				list($year, $month, $day) = explode('-', date('Y-m-d', $strToTime), 3);
			}
			else {
				$valid = false;
			}
			$valid = $valid && isset($day, $month, $year);
		}
		if ($this->getShowTimeFields()) {
			$hour = !empty($value['Hour']) ? $value['Hour'] : null;
			$min = !empty($value['Minute']) ? $value['Minute'] : null;
			if ($this->config['include_seconds']) {
				$second = !empty($value['Second']) ? $value['Second'] : null;
			}
			if (!$this->config['24_hr']) {
				$period = !empty($value['Period']) ? $value['Period'] : null;
				$valid = $valid && isset($period);
			}
		}
		if ($period == 'pm') {
			$hour += 12;
		}
		if($valid) {
			$date = mktime($hour, $min, $second, $month, $day, $year);
			$valid = $valid && $date !== false;
			if ($valid) {
				$date = DBField::create_field('SS_Datetime', $date);
				$this->valueObj = $date;
				Debug::dump($date);
				die;
			}
			else {
				$this->setError(_t($this->class, 'INVALIDDATE', 'Invalid date passed'), 'validation bad');
			}
		}
		return $valid;
	}

	public function setValue($value) {
		if (is_array($value)) {
			$this->validateDateArray($value);
			//handle the date
			$day = $value['Day'];
			$month = $value['Month'];
			$year = !empty($value['Year']) ? $value['Year'] : null;
			//handle the time
				//handle 12 hr time
				//handle 24 hr time
			//store as string and as object
		}
		if (!$this->config['dmy_fields']) {
			Debug::endshow($value);
		}
		else {
		}
		return parent::setValue($value);
	}

	public function Value() {
		return parent::Value();
	}

	public function getShowTimeFields() {
		return $this->showTime;
	}

	public function setShowTimeFields(bool $bool) {
		$this->showTime = $bool;
		return $this;
	}

	public function getShowDateFields() {
		return $this->showDate;
	}

	public function setShowDateFields(bool $bool) {
		$this->showDate = $bool;
		return $this;
	}

	public function getYear() {
		/*if ($this->valueObj) {
			return $this->valueObj->Format('Y');
		}
		else*/if (!empty($this->value['Year'])) {
			return $this->value['Year'];
		}
	}

	public function getMonth() {
		/*if ($this->valueObj) {
			return $this->valueObj->Format('m');
		}
		else*/if (!empty($this->value['Month'])) {
			return $this->value['Month'];
		}
	}

	public function getDay() {
		/*if ($this->valueObj) {
			return $this->valueObj->Format('d');
		}
		else*/if (!empty($this->value['Day'])) {
			return $this->value['Day'];
		}
	}

	public function getHour() {
		/*if ($this->valueObj) {
			$format = $this->config['24_hr'] ? 'H' : 'h';
			return $this->valueObj->Format($format);
		}
		else*/if (!empty($this->value['Hour'])) {
			return $this->value['Hour'];
		}
	}

	public function getMinute() {
		/*if ($this->valueObj) {
			return $this->valueObj->Format('i');
		}
		else*/if (!empty($this->value['Minute'])) {
			return $this->value['Minute'];
		}
	}

	public function getSecond() {
		/*if ($this->valueObj) {
			return $this->valueObj->Format('s');
		}
		else*/if (!empty($this->value['Second'])) {
			return $this->value['Second'];
		}
	}

	public function getPeriod() {
		/*if ($this->valueObj) {
			return $this->valueObj->Format('a');
		}
		else*/if (!empty($this->value['Period'])) {
			return $this->value['Period'];
		}
	}

	private function createFieldRange($fieldName) {
		$rangeMax = $this->config['max_dropdown_values'][$fieldName];
		$rangeMin = $this->config['min_dropdown_values'][$fieldName];
		if ($fieldName == 'Hour' && !$this->config['24_hr']) {
			if ($rangeMax > 12) {
				$rangeMax = 12;
			}
			if ($rangeMin < 1) {
				$rangeMin = 1;
			}
		}
		$range = range(
			$rangeMin,
			$rangeMax,
			$this->config[strtolower($fieldName) . '_interval']
		);
		foreach ($range as &$val) {
			if ($val > 9) {
				break;
			}
			$val = '0' . $val;
		}
		return ArrayLib::valuekey($range);
	}

	public function getTimeField() {
		$fields = CompositeField::create(
			DropdownField::create(
				$this->getName() . '[Hour]', 'Hour',
				$this->createFieldRange('Hour'),
				$this->getHour()
			),
			DropdownField::create($this->getName() . '[Minute]',
				'Minute',
				$this->createFieldRange('Minute'),
				$this->getMinute()
			)
		)->addExtraClass($this->config['time_field_classes']);
		if ($this->config['include_seconds']) {
			$fields->push(DropdownField::create(
				$this->getName() . '[Second]',
				'Second',
				$this->createFieldRange('Second'),
				$this->getSecond()
			));
		}
		if (!$this->config['24_hr']) {
			$fields->push(DropdownField::create(
				$this->getName() . '[Period]',
				'Period',
				array(
					'am',
					'pm'
				),
				$this->getPeriod()
			));
		}
		return $fields;
	}

	public function getDateField() {
		if (!$this->config['dmy_fields']) {
			return CompositeField::create(DateField::create(
				$this->getName() . '[Date]',
				'',
				$this->valueObj
			))->addExtraClass($this->config['date_field_classes']);
		}
		$class = $this->config['use_date_dropdown'] ? 'DropdownField' : 'NumericField';
		$fields = array(
			'Day' => $class::create($this->getName() . '[Day]', 'Day')
				->setAttribute('placeholder', 'Day'),
			'Month' => $class::create($this->getName() . '[Month]', 'Month')
				->setAttribute('placeholder', 'Month'),
			'Year' => $class::create($this->getName() . '[Year]', 'Year')
				->setAttribute('placeholder', 'Year'),
		);
		$composite = CompositeField::create()->addExtraClass(
			$this->config['date_field_classes']
		);
		foreach ($this->config['date_field_order'] as $order) {
			$field = $fields[$order];
			if ($this->config['use_date_dropdown']) {
				$field->setSource($this->createFieldRange($order));
			}
			$field->setValue($this->{'get' . $order}());
			$composite->push($field);
		}
		return $composite;
	}

}
