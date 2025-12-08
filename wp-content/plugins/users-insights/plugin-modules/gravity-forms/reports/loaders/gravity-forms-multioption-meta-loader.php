<?php

class USIN_Gravity_Forms_Multioption_Meta_Loader extends USIN_Multioption_Field_Loader{


	protected function value_to_array($value){
		if($this->report->options['is_json']){
			return json_decode($value);
		}else{
			// sometimes values are stored with space after comma, other times without space
			return array_map('trim', explode(',', $value));
		}
	}

}