<?php
/**
 * NeosTag 
 * @copyright   Bill Rocha - http://plus.google.com/+BillRocha
 * @license     MIT & GLP2
 * @author      Bill Rocha - prbr@ymail.com
 * @version     0.0.1
 * @package     Limp/Doc/Ntag
 * @access      public
 * @since       0.0.4
 *
 * ©NeosTag é marca registrada da NeosOrg e todos os direitos são reservados.
 */

namespace Limp\Doc\Ntag;

class Select {

	public $data = [];
	public $default = '';


	function __construct($data = null, $default = null)
	{
		$this->data = $data;
		$this->default = $default;
		\Limp\App\App::e($this);
	}


	function setData(Array $data)
	{
		$this->data = $data;
	}

	function setDefault($default)
	{
		$this->default = $default;
	}

	function get()
	{
		$o = '<select>';
		foreach ($this->data as $k => $v) {
			$o .= '<option value="" '.($k == $this->default ? 'selected' : '').'>'.$v.'</option>';
		}
		$o .= '</selct>';

		return $o;
	}


}