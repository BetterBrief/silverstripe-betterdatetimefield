<?php

/**
 * A better DatetimeField, because the core ones suck
 *
 * @todo Date picker in the front end
 * @todo Document
 * @todo Validat
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

	public function setValue($value) {
		if (!$this->config['dmy_fields']) {
			Debug::endshow($value);
		}
		else {
			$value = sprintf(
				'%d-%d-%d %d:%d',
				$value['Year'],
				$value['Month'],
				$value['Day'],
				$value['Hour'],
				$value['Minute']
			);
		}
		$return = parent::setValue($value);
		$this->valueObj = DBField::create_field('SS_Datetime', $value);
		return $return;
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
		if ($this->valueObj) {
			return $this->valueObj->Year();
		}
	}

	public function getMonth() {
		if ($this->valueObj) {
			return $this->valueObj->Month();
		}
	}

	public function getDay() {
		if ($this->valueObj) {
			return $this->valueObj->DayOfMonth();
		}
	}

	public function getHour() {
		if ($this->valueObj) {
			return $this->valueObj->Format('H');
		}
	}

	public function getMinute() {
		if ($this->valueObj) {
			return $this->valueObj->Format('i');
		}
	}

	public function getSecond() {
		if ($this->valueObj) {
			return $this->valueObj->Format('s');
		}
	}

	private function createFieldRange($fieldName) {
		if ($fieldName == 'Hour') {
			$rangeMax = $this->config['24_hr'] ? 23 : 12;
			$rangeMin = $this->config['24_hr'] ? 0 : 1;
		}
		else {
			$rangeMax = $this->config['max_dropdown_values'][$fieldName];
			$rangeMin = $this->config['min_dropdown_values'][$fieldName];
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
		return $range;
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
