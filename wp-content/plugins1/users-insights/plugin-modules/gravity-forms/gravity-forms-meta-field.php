<?php

class USIN_Gravity_Forms_Meta_Field extends USIN_Gravity_Forms_Field{

	protected $meta_field = null;

	public function __construct($gf_field, $meta_field){
		parent::__construct($gf_field);
		$this->meta_field = $meta_field;
	}

	public function get_id(){
		return $this->meta_field['key'] == 'gf_custom' ? $this->meta_field['custom_key'] : $this->meta_field['key'];
	}

	public function get_name(){
		$name = parent::get_name();
		if($this->is_list_subfield()){
			$name .= $this->get_list_subfield_name();
		}
		return $name;
	}

	public function get_linked_id(){
		return $this->meta_field['value'];
	}

	/**
	 * If the field ID is in a float format, such as 20.1 , it means that it is a subfield
	 * @return boolean           [description]
	 */
	protected function is_list_subfield() {
		$linked_id = (string)$this->get_linked_id();
		return $this->is_list_field() && strpos($linked_id, '.') !== false;
	}

	/**
	 * For fields that contain subfields (such as List fields), when the subfield
	 * is registered as a separate field, retrieve the name of this subfield, as
	 * otherwise it would show the parent field name only.
	 * @return string           the subfield name if found or empty string otherwise
	 */
	protected function get_list_subfield_name(){
		$linked_id = $this->get_linked_id();

		if(isset($this->gf_field->inputs)){
			//find the input element with the same id as $linked_id
			$options = $this->gf_field->inputs;
			foreach ($this->gf_field->inputs as $input) {
				if($input['id'] == $linked_id){
					return ' ('.$input['label'].')';
				}
			}
		}elseif(isset($this->gf_field->choices)){
			$options = $this->gf_field->choices;
			//get the index of the choice item
			//if the field ID is 20.3 , the index would be 3
			list($int,$dec)=explode('.', $linked_id);
			if(isset($options[$dec])){
				return ' ('.$options[$dec]['text'].')';
			}
		}
		
		return '';
	}

}