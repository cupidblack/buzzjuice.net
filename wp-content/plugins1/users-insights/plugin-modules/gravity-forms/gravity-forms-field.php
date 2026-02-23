<?php

class USIN_Gravity_Forms_Field{

	const PREFIX = 'gf_';
	public $gf_field = null;

	public function __construct($gf_field){
		$this->gf_field = $gf_field;
	}

	public function get_id(){
		return $this->gf_field->id;
	}

	public function get_prefix(){
		return self::PREFIX;
	}

	public function get_prefixed_id(){
		return $this->get_prefix().$this->get_id();
	}

	public function get_name(){
		if(!empty($this->gf_field->adminLabel)){
			return $this->gf_field->adminLabel;
		}else{
			return $this->gf_field->label;
		}
	}

	public function get_filter_options(){
		$type = $this->get_type();
		$filter = array(
			'type' => $type
		);
		if($type == 'select'){
			$filter['options'] = $this->get_field_options();
		}elseif($type == 'date'){
			$filter['yearsRange'] = array(-100, 20);
		}

		return $filter;
	}

	/**
	 * Matches an Gravity Forms field type to Users Insights field type.
	 * @return string          the corresponding Users Insights field type
	 */
	public function get_type(){
		switch ($this->gf_field->type) {
			case 'number':
			case 'quantity':
			case 'total':
				return 'number';
				break;
			case 'date':
				return 'date';
				break;
			case 'select':
			case 'radio':
				return 'select';
				break;
			case 'list':
			case 'multiselect':
			case 'checkbox':
				return 'multioption_text';
				break;
			default:
				return 'text';
				break;
		}
	}

	public function get_gf_type(){
		return $this->gf_field->type;
	}

	/**
	 * Returns the type of the field when it is used in the combined filters section.
	 * It will return null if the field is not filtrable in the combined filters section.
	 */
	public function get_subfield_type(){
		$type = $this->get_type();
		if(in_array($type, array('number', 'date', 'select'))){
			return $type;
		}
		
		switch ($this->gf_field->type) {
			case 'checkbox':
				return 'select';
				break;
			case 'multiselect':
			case 'text':
			case 'textarea':
			case 'phone':
			case 'email':
				return 'text';
				break;
		}
	}

	/**
	 * For select type fields (drop-down, radio, etc.), retrieves the registered
	 * options and converts them into a Users Insights option format.
	 * @return array           the options in a Users Insights format
	 */
	public function get_field_options(){
		$options = array();
		
		if(!empty($this->gf_field->choices)){
			foreach ($this->gf_field->choices	as $ch) {
				$options[]=array('key'=>$ch['value'], 'val'=>$ch['text']);
			}
		}
		
		return $options;
	}

	public function is_json_field(){
		return $this->is_list_json_field() || $this->is_multiselect_json_field();
	}

	public function is_list_json_field(){
		return $this->is_list_field() && !empty($this->gf_field->choices);	
	}

	public function is_multiselect_json_field(){
		// old versions of GF store multiselects as comma delimited values and newer versions store them as JSON format
		return $this->gf_field->type == 'multiselect' && isset($this->gf_field->storageType) && $this->gf_field->storageType == 'json';
	}

	public function format_json_field_data($val){
		if($this->is_list_json_field()){
			return $this->format_list_json_field_data($val);
		}else{
			return $this->format_multiselect_json_field_data($val);
		}
	}

	protected function is_list_field(){
		return $this->gf_field->type == 'list';
	}

	protected function format_list_json_field_data($val){
		$dec = json_decode($val);
		$vals = array();
		if(is_array($dec)){
			foreach ($dec as $val ) {
				if(is_object($val)){
					$subvals = array();
					foreach ($val as $k => $v) {
						if(!empty($v)){
							$subvals[]= "$k: $v";
						}
					}
					$vals[]=implode(', ', $subvals);
				}
			}
		}
		if(!empty($vals)){
			return implode(' | ', $vals);
		}
		return $val;
	}

	protected function format_multiselect_json_field_data($val){
		if(empty($val)){
			return $val;
		}
		$values = json_decode($val);
		return is_array($values) ? implode(', ', $values) : $val;
	}
}