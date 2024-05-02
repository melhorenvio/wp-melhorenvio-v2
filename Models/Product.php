<?php

namespace MelhorEnvio\Models;

class Product
{
	public $id;
	public $name;
	public $quantity;
	public $weight;
	public $height;
	public $width;
	public $length;
	public $unitary_value;
	public $insurance_value;
	public $type;
	public $is_virtual;
	public $components = [];
	public $parentId;
	public $pricing;
	public $shipping_fee;

	public function setValues($value){
		$this->unitary_value = (float) $value;
		$this->insurance_value = (float) $value;
	}
}
