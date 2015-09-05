<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Form {
	/**
	 * Super Class
	 *
	 * @package     CodeIgniter
	 * @subpackage  Form library
	 * @category    Library
	 * @author      Rafael Américo Sartori
	 * @link        http://example.com
	 */
	 
	/**
	 * Used to store all the form element controllers as objects
	 *
	 * @var [StdClass]
	 */
	 private $controllers = array();
	 
	/**
	 * Used to store whitch function should be called when a certain kind of controller is going to be added
	 *
	 * @var array($attribute(String) => $value(String))
	 */
	 private $controllers_push_function = array();
	 
	/**
	 * Used to store html attributes that are common for all controllers. This ones are only added at the end, when code is processed
	 *
	 * @var array($attribute(String) => $value(String))
	 */
	 private $global_attributes = array();
	 
	/**
	 * Class constructor
	 *
	 * Loads some necessary libraries and helpers
	 *
	 * @uses	CI
	 */
    public function __construct()
    {
		$CI =& get_instance();
		$CI->load->helper('form');
		
		// Register field types and respective functions to call when creating them (returning objects)
		$this->register_controller('text', 'input_object');
		$this->register_controller('password', 'input_object');
		$this->register_controller('upload', 'input_object');
		$this->register_controller('hidden', 'input_object');
		$this->register_controller('textarea', 'input_object');
		
		$this->register_controller('dropdown', 'combo_object');
		$this->register_controller('multiselect', 'combo_object');
		
		$this->register_controller('radio', 'radio_object');
		$this->register_controller('checkbox', 'radio_object');
		
		$this->register_controller('submit', 'input_object');
		$this->register_controller('button', 'input_object');
		$this->register_controller('reset', 'input_object');
		
		$this->register_controller('range', 'special_input_object');
    }
	
	/**
	 * Class magic method
	 *
	 * This magic method is used to allow insertion of new controllers into $controller.
	 *
	 */
	public function __call($controller, $parameters)
	{
		// In case user wants to add a new controller
		if (isset($this->controllers_push_function[$controller]))
		{
			$function = $this->controllers_push_function[$controller];
			$parameters = array_merge(array($controller), $parameters); // Passes the input type function
			call_user_func_array(array($this, $function), $parameters);
		}
	}
	
	/**
	 * Class magic method
	 *
	 * This magic method is used to allow interaction with controllers already created and stored into $controller.
	 *
	 */
	public function __get($name)
	{
		// In case user wants to interact with a controller
		if (isset($this->controllers[$name]))
		{
			return $this->controllers[$name];
		}
		trigger_error("$name does not exist!");
	}
	
	/**
	 * Register new controller types and respective functions to be called
	 *
	 * @param		string		$name					Name of the controller. It's going to be used when user call for $this->form->$name(). When $name = input: $this->form->input()
	 * @param		callback	$callback				Name of the function to be called
	 * @return		void
	 */
	public function register_controller($name, $callback)
	{
		$this->controllers_push_function[$name] = $callback;
	}
	
	/**
	 * Add a new controller to the $controllers variable
	 *
	 * @param	object	$object		Object representing the controller
	 * @return	object	Controller's object
	 */
	private function push_controller($object)
	{
		$this->controllers[$object->name] = $object;
		return $this->controllers[$object->name];
	}
	
	public function output()
	{
		$output_object = array();
		
		$output_object['open'] = form_open_multipart();
		
		foreach ($this->controllers as $controller)
		{
			$html = $controller->output_function; // It can't be done directly...
			
			$output_controller = array(
				'type' => $controller->type,
				'name' => $controller->name,
				'title' => (isset($controller->title)) ? $controller->title : $controller->name,
				'html' => $html($controller)
			);
			
			$output_object['fields'][$controller->name] = (object)$output_controller;
		}
		
		$this->clear();
		return (object)$output_object;
	}
	
	/**
	 * Sets attributes there are applied by default for every controller. This value is replaced by any specific value supplied specially for the controller
	 *
	 * @param	miexed	$attribute	Array of pairs ($attribute -> $value) or a string containing the attribute name
	 * @param	string	$value		If informed, it is the value of the attribute
	 * @return	void
	 */
	public function set_global_attribute($attribute, $value = "")
	{
		if (is_array($attribute))
		{
			$this->global_attributes += $attribute;
		}
		else
		{
			$this->global_attributes[$attribute] = $value;
		}
	}
	
	/**
	 * Unsets attributes there are applied by default for every controller.
	 *
	 * @param	miexed	$attribute	Array of attributes or a string containing the attribute name
	 * @return	void
	 */
	public function unset_global_attribute($attribute, $value = "")
	{
		if ( ! is_array($attribute))
		{
			$attribute = array($attribute);
		}
		foreach ($attribute as $attr)
		{
			unset($this->gobal_attributes[$attr]);
		}
	}
	
	/**
	 * Unsets previously created controllers
	 *
	 * @param	miexed	$attribute	Array of controllers or a string containing the controller name
	 * @return	void
	 */
	public function unset_controller($controllers)
	{
		if ( ! is_array($controllers))
		{
			$controllers = array($controllers);
		}
		foreach ($controllers as $controller)
		{
			unset($this->controllers[$controller]);
		}
	}
	
	/**
	 * Clear all the controllers created and reset the library
	 *
	 * @return	void
	 */
	public function clear()
	{
		$this->controllers = array();
		$this->global_attributes = array();
	}
	
	/**
	 * Sets default values for a more than one controller at once. This is useful when populating a form with database data, for sample.
	 *
	 * @param	array	$values		Array of pairs ($controller_name => $default_value)
	 * @return	void
	 */
	public function set_values($values)
	{
		foreach ($values as $controller => $value)
		{
			if (isset($this->controllers[$controller]))
			{
				$this->controllers[$controller]->value = $value;
			}
		}
	}
	
	/**
	 * Field constructors section
	 * All functions here creates pushes an object into $controllers and returns the object. This is useful for setting up more details of the controller
	 * When creating an object, you must always provide an output_function to process the object into HTML.
	 * All functions must have at least one first parameter: $controller_type, that is passed when user tries to add a new controller.
	 * An object must follow the following protocol, at least:
	 *
	 *		<code>
	 *		$object = array(
	 *			'name' => $name,
	 *			'type' => $controller_type,
	 *			'title' => $title,
	 *			'output_function' => function($self_object) use($controller_type) {}
	 *		);
	 *		</code>
	 * 
	 * The output functions receives the object itself and is expected to output a HTML string.
	 * If the tag supports attributes, don't forget to merge them with the global attributes. It should be done inside the output_function.
	 *
	 */
	 
	public function input_object($controller_type, $name, $title = "", $value = "", $attrs = array())
	{
		$object = array(
			'name' => $name,
			'value'=> $value,
			'type' => $controller_type,
			'title' => $title,
			'attributes' => $attrs,
			'output_function' => function($self_object) use($controller_type) {
				$self_object->attributes = $this->global_attributes + $self_object->attributes;
				$helper_function = 'form_' . $controller_type;
				return $helper_function($self_object->name, $self_object->value, $self_object->attributes);
			}
		);

		$this->push_controller((object)$object);
	}
	
	public function special_input_object($controller_type, $name, $title = "", $value = "", $attrs = array())
	{
		$object = array(
			'name' => $name,
			'value'=> $value,
			'type' => $controller_type,
			'title' => $title,
			'attributes' => $attrs,
			'output_function' => function($self_object) use($controller_type) {
				$self_object->attributes = $this->global_attributes + $self_object->attributes;
				$self_object->attributes['value'] = $self_object->value;
				$self_object->attributes['type'] = $self_object->type;
				
				$html_output = "<input ";
				
				foreach ($self_object->attributes as $attr => $value)
				{
					$html_output .= $attr . '="' . $value . '"';
				}
				$html_output .= ' />';
				return $html_output;
			}
		);

		$this->push_controller((object)$object);
	}
	
	public function combo_object($controller_type, $name, $title = "", $options = array(), $value = "", $attrs = array())
	{
		$object = array(
			'name' => $name,
			'value'=> $value,
			'type' => $controller_type,
			'options' => $options,
			'title' => $title,
			'attributes' => $attrs,
			'output_function' => function($self_object) use($controller_type) {
				$self_object->attributes = $this->global_attributes + $self_object->attributes;
				$helper_function = 'form_' . $controller_type;
				return $helper_function($self_object->name, $self_object->options, $self_object->value, $self_object->attributes);
			}
		);

		$this->push_controller((object)$object);
	}
	
	public function radio_object($controller_type, $name, $title = "", $options = array(), $value = "", $attrs = array())
	{
		$object = array(
			'name' => $name,
			'value'=> $value,
			'type' => $controller_type,
			'options' => $options,
			'title' => $title,
			'attributes' => $attrs,
			'output_function' => function($self_object) use($controller_type) {
				$self_object->attributes = $this->global_attributes + $self_object->attributes;
				$html_output = '';
				$helper_function = 'form_' . $controller_type;
				
				foreach ($self_object->options as $val => $name)
				{
					$is_checked = (@in_array($val, (array)$value) === TRUE) ? TRUE : FALSE;
					$html_output .= '<label>' . $helper_function($self_object->name, $val, @$is_checked) . ' ' .$name . '</label>';
				}
				
				return $html_output;
			}
		);

		$this->push_controller((object)$object);
	}
	
	/**
	 * End of field constructors section
	 */
	
	public function debug() {
		var_dump($this->controllers);
	}
}

/* End of file Form.php */