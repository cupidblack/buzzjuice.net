<?php

class USIN_Db_Map_Field{
	public $id = null;
	public $options = null;

	public function __construct($id, $options){
		$this->id = $id;
		$this->options = $options;
	}

	public function get_ref(){
		return $this->options['db_ref'];
	}

	public function get_table(){
		//if no table is set, use the field key as reference
		return empty($this->options['db_table']) ? $this->id : $this->options['db_table'];
	}

	public function has_select(){
		return !$this->is_option_enabled('no_select');
	}

	public function get_cast_type(){
		if(isset($this->options['cast'])){
			return $this->options['cast'];
		}
	}

	public function nulls_are_last(){
		return $this->is_option_enabled('nulls_last');
	}

	public function should_set_alias(){
		return $this->is_option_enabled('set_alias');
	}

	public function has_custom_select(){
		return $this->is_option_enabled('custom_select');
	}

	public function converts_null_to_zero(){
		return $this->is_option_enabled('null_to_zero');
	}

	public function stores_empty_string(){
		return $this->is_option_enabled('stores_empty_str');
	}

	public function is_utc(){
		return $this->is_option_enabled('utc');
	}

	public function is_option_enabled($option_name){
		return isset($this->options[$option_name]) && $this->options[$option_name] === true;
	}

	public function is_comparable_to_empty_string(){
		$is_date_field = usin_options()->get_field_type($this->id) == 'date';
		return !$is_date_field || $this->stores_empty_string();
	}
}